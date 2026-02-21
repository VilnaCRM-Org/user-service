<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Shared\Infrastructure\Factory\UuidFactory as SharedUuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\RefreshTokenCommand;
use App\User\Application\CommandHandler\RefreshTokenCommandHandler;
use App\User\Application\DTO\RefreshTokenCommandResponse;
use App\User\Application\Factory\AuthTokenFactoryInterface;
use App\User\Application\Service\RefreshTokenEventPublisherInterface;
use App\User\Domain\Contract\AccessTokenGeneratorInterface;
use App\User\Domain\Entity\AuthRefreshToken;
use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Repository\AuthRefreshTokenRepositoryInterface;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Uid\Ulid;

final class RefreshTokenCommandHandlerGraceWindowTest extends UnitTestCase
{
    private AuthRefreshTokenRepositoryInterface&MockObject $refreshTokenRepository;
    private AuthSessionRepositoryInterface&MockObject $authSessionRepository;
    private UserRepositoryInterface&MockObject $userRepository;
    private AccessTokenGeneratorInterface&MockObject $accessTokenGenerator;
    private RefreshTokenEventPublisherInterface&MockObject $eventPublisher;
    private AuthTokenFactoryInterface&MockObject $authTokenFactory;
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
        $this->eventPublisher = $this->createMock(RefreshTokenEventPublisherInterface::class);
        $this->authTokenFactory = $this->createMock(AuthTokenFactoryInterface::class);
        $this->configureRefreshTokenResponseFactory();
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
        $this->expectDoubleGraceUseEvent();

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid refresh token.');

        $this->executeRefresh($plainToken);
    }

    private function configureRefreshTokenResponseFactory(): void
    {
        $this->authTokenFactory
            ->method('createRefreshTokenResponse')
            ->willReturnCallback(
                static fn (
                    string $accessToken,
                    string $refreshToken
                ): RefreshTokenCommandResponse => new RefreshTokenCommandResponse(
                    $accessToken,
                    $refreshToken
                )
            );
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
            $this->authTokenFactory,
            $this->eventPublisher,
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
        $this->authTokenFactory->method('generateOpaqueToken')
            ->willReturn('test-opaque-token-1234567890-abcdefghijklmn');
        $this->configureRefreshTokenFactory();
        $this->configureJwtPayloadFactory();
    }

    private function configureRefreshTokenFactory(): void
    {
        $this->authTokenFactory->method('createRefreshToken')
            ->willReturnCallback(
                static fn (
                    string $sessionId,
                    string $plain,
                    DateTimeImmutable $issuedAt
                ): AuthRefreshToken => new AuthRefreshToken(
                    (string) new Ulid(),
                    $sessionId,
                    $plain,
                    $issuedAt->modify('+1 month')
                )
            );
    }

    private function configureJwtPayloadFactory(): void
    {
        $this->authTokenFactory->method('buildJwtPayload')
            ->willReturnCallback(
                static fn (User $user, string $sessionId, DateTimeImmutable $issuedAt): array => [
                    'sub' => $user->getId(),
                    'iss' => 'vilnacrm-user-service',
                    'aud' => 'vilnacrm-api',
                    'exp' => $issuedAt->getTimestamp() + 900,
                    'iat' => $issuedAt->getTimestamp(),
                    'nbf' => $issuedAt->getTimestamp(),
                    'jti' => 'test-jti',
                    'sid' => $sessionId,
                    'roles' => ['ROLE_USER'],
                ]
            );
    }

    private function expectAccessToken(string $accessToken): void
    {
        $this->accessTokenGenerator->expects($this->once())->method('generate')
            ->willReturn($accessToken);
    }

    private function expectRotatedEvent(): void
    {
        $this->eventPublisher->expects($this->once())->method('publishRotated')
            ->with($this->anything(), $this->anything());
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
        $this->eventPublisher->expects($this->once())->method('publishTheftDetected')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->anything(),
                'double_grace_use'
            );
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
