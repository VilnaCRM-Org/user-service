<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Infrastructure\Factory\UuidFactory as SharedUuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\RefreshTokenCommand;
use App\User\Application\CommandHandler\RefreshTokenCommandHandler;
use App\User\Domain\Contract\AccessTokenGeneratorInterface;
use App\User\Domain\Entity\AuthRefreshToken;
use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Entity\User;
use App\User\Domain\Event\RefreshTokenRotatedEvent;
use App\User\Domain\Event\RefreshTokenTheftDetectedEvent;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Repository\AuthRefreshTokenRepositoryInterface;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Uid\Factory\UlidFactory;
use Symfony\Component\Uid\Factory\UuidFactory;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;

final class RefreshTokenCommandHandlerGraceWindowTest extends UnitTestCase
{
    private AuthRefreshTokenRepositoryInterface&MockObject $refreshTokenRepository;
    private AuthSessionRepositoryInterface&MockObject $authSessionRepository;
    private UserRepositoryInterface&MockObject $userRepository;
    private AccessTokenGeneratorInterface&MockObject $accessTokenGenerator;
    private EventBusInterface&MockObject $eventBus;
    private UuidFactory&MockObject $uuidFactory;
    private UlidFactory&MockObject $ulidFactory;
    private UserFactory $userFactory;
    private UuidTransformer $uuidTransformer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->refreshTokenRepository = $this->createMock(
            AuthRefreshTokenRepositoryInterface::class
        );
        $this->authSessionRepository = $this->createMock(AuthSessionRepositoryInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->accessTokenGenerator = $this->createMock(AccessTokenGeneratorInterface::class);
        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->uuidFactory = $this->createMock(UuidFactory::class);
        $this->ulidFactory = $this->createMock(UlidFactory::class);
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer(new SharedUuidFactory());
    }

    public function testGraceWindowCanBeConfigured(): void
    {
        $plainToken = 'custom-grace-window-token';
        $token = $this->createRotatedToken($plainToken, '-120 seconds');
        [$session, $user] = $this->createTokenContext($token);

        $this->expectCommonTokenContextLookups($token, $session, $user);
        $this->expectGraceReuseEligible();
        $this->expectRandomIdFactories();
        $this->expectAccessToken('custom-grace-token');
        $this->expectRotatedEvent();

        $command = $this->executeRefresh($plainToken, 180);

        $this->assertSame('custom-grace-token', $command->getResponse()->getAccessToken());
        $this->assertOpaqueTokenFormat($command->getResponse()->getRefreshToken());
        $this->assertTrue($token->isGraceUsed());
    }

    public function testGraceWindowStartIsComputedInPast(): void
    {
        $plainToken = 'grace-window-order-token';
        $token = $this->createRotatedToken($plainToken, '-30 seconds');
        [$session, $user] = $this->createTokenContext($token);

        $this->expectCommonTokenContextLookups($token, $session, $user);
        $this->expectPastGraceWindowStart($plainToken);
        $this->expectRandomIdFactories();
        $this->expectAccessToken('grace-window-access-token');
        $this->expectRotatedEvent();

        $command = $this->executeRefresh($plainToken);

        $this->assertSame('grace-window-access-token', $command->getResponse()->getAccessToken());
    }

    public function testGraceUsedTokenTriggersTheftWithoutGraceEligibilityCheck(): void
    {
        $plainToken = 'double-grace-token';
        $token = $this->createRotatedToken($plainToken, '-30 seconds');
        $token->markGraceUsed();
        [$session, $user] = $this->createTokenContext($token);

        $this->expectCommonTokenContextLookups($token, $session, $user);
        $this->expectDoubleGraceUseTheftFlow($token, $session);
        $this->expectUuidFactory();
        $this->expectDoubleGraceUseEvent();

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid refresh token.');

        $this->executeRefresh($plainToken);
    }

    private function assertOpaqueTokenFormat(string $token): void
    {
        $this->assertSame(43, strlen($token));
        $this->assertStringNotContainsString('=', $token);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9\-_]+$/', $token);
    }

    private function createHandler(
        int $refreshTokenGraceWindowSeconds = 60
    ): RefreshTokenCommandHandler {
        return new RefreshTokenCommandHandler(
            $this->refreshTokenRepository,
            $this->authSessionRepository,
            $this->userRepository,
            $this->accessTokenGenerator,
            $this->eventBus,
            $this->uuidFactory,
            $this->ulidFactory,
            $refreshTokenGraceWindowSeconds,
        );
    }

    private function createRotatedToken(string $plainToken, string $rotatedAt): AuthRefreshToken
    {
        $token = $this->createValidRefreshToken($plainToken);
        $token->markAsRotated(new DateTimeImmutable($rotatedAt));

        return $token;
    }

    /**
     * @return array{AuthSession, User}
     */
    private function createTokenContext(AuthRefreshToken $token): array
    {
        return [$this->createValidSession($token->getSessionId()), $this->createUser()];
    }

    private function expectCommonTokenContextLookups(
        AuthRefreshToken $token,
        AuthSession $session,
        User $user
    ): void {
        $this->refreshTokenRepository->expects($this->once())->method('findByTokenHash')
            ->willReturn($token);
        $this->authSessionRepository->expects($this->once())->method('findById')
            ->willReturn($session);
        $this->userRepository->expects($this->once())->method('findById')
            ->willReturn($user);
        $this->refreshTokenRepository->expects($this->once())->method('save');
    }

    private function expectGraceReuseEligible(): void
    {
        $this->refreshTokenRepository->expects($this->once())->method('markGraceUsedIfEligible')
            ->willReturn(true);
    }

    private function expectPastGraceWindowStart(string $plainToken): void
    {
        $this->refreshTokenRepository->expects($this->once())->method('markGraceUsedIfEligible')
            ->willReturnCallback(static function (
                string $tokenHash,
                DateTimeImmutable $graceWindowStartedAt,
                DateTimeImmutable $currentTime
            ) use ($plainToken): bool {
                return $tokenHash === hash('sha256', $plainToken)
                    && $graceWindowStartedAt->getTimestamp() <= $currentTime->getTimestamp();
            });
    }

    private function expectRandomIdFactories(): void
    {
        $this->ulidFactory->method('create')->willReturnCallback(static fn () => new Ulid());
        $this->expectUuidFactory();
    }

    private function expectUuidFactory(): void
    {
        $this->uuidFactory->method('create')->willReturnCallback(static fn () => Uuid::v4());
    }

    private function expectAccessToken(string $accessToken): void
    {
        $this->accessTokenGenerator->expects($this->once())->method('generate')
            ->willReturn($accessToken);
    }

    private function expectRotatedEvent(): void
    {
        $this->eventBus->expects($this->once())->method('publish')
            ->with($this->isInstanceOf(RefreshTokenRotatedEvent::class));
    }

    private function expectDoubleGraceUseTheftFlow(
        AuthRefreshToken $token,
        AuthSession $session
    ): void {
        $this->refreshTokenRepository->expects($this->never())->method('markGraceUsedIfEligible');
        $this->refreshTokenRepository->expects($this->once())->method('findBySessionId')
            ->with($session->getId())->willReturn([$token]);
        $this->refreshTokenRepository->expects($this->once())->method('save')
            ->with($this->callback(
                static fn (AuthRefreshToken $savedToken): bool => $savedToken->isRevoked()
            ));
        $this->authSessionRepository->expects($this->once())->method('save')
            ->with($this->callback(
                static fn (AuthSession $savedSession): bool => $savedSession->isRevoked()
            ));
    }

    private function expectDoubleGraceUseEvent(): void
    {
        $this->eventBus->expects($this->once())->method('publish')
            ->with($this->callback(
                static function (RefreshTokenTheftDetectedEvent $event): bool {
                    return $event->reason === 'double_grace_use';
                }
            ));
    }

    private function executeRefresh(
        string $plainToken,
        int $graceWindowSeconds = 60
    ): RefreshTokenCommand {
        $command = new RefreshTokenCommand($plainToken);
        $this->createHandler($graceWindowSeconds)->__invoke($command);

        return $command;
    }

    private function createValidRefreshToken(string $plainToken): AuthRefreshToken
    {
        return new AuthRefreshToken(
            (string) new Ulid(),
            (string) new Ulid(),
            $plainToken,
            new DateTimeImmutable('+1 month')
        );
    }

    private function createValidSession(string $sessionId): AuthSession
    {
        $createdAt = new DateTimeImmutable('-5 minutes');

        return new AuthSession(
            $sessionId,
            $this->faker->uuid(),
            '127.0.0.1',
            'Test Agent',
            $createdAt,
            $createdAt->modify('+15 minutes'),
            false
        );
    }

    private function createUser(): User
    {
        return $this->userFactory->create(
            $this->faker->email(),
            $this->faker->firstName(),
            $this->faker->password(),
            $this->uuidTransformer->transformFromString(
                $this->faker->uuid()
            )
        );
    }
}
