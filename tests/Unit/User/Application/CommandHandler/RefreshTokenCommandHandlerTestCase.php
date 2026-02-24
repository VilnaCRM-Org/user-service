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

abstract class RefreshTokenCommandHandlerTestCase extends UnitTestCase
{
    protected AuthRefreshTokenRepositoryInterface&MockObject $refreshTokenRepository;
    protected AuthSessionRepositoryInterface&MockObject $authSessionRepository;
    protected UserRepositoryInterface&MockObject $userRepository;
    protected AccessTokenGeneratorInterface&MockObject $accessTokenGenerator;
    protected RefreshTokenEventPublisherInterface&MockObject $eventPublisher;
    protected AuthTokenFactoryInterface&MockObject $authTokenFactory;
    protected UserFactory $userFactory;
    protected UuidTransformer $uuidTransformer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->refreshTokenRepository =
            $this->createMock(AuthRefreshTokenRepositoryInterface::class);
        $this->authSessionRepository =
            $this->createMock(AuthSessionRepositoryInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->accessTokenGenerator = $this->createMock(AccessTokenGeneratorInterface::class);
        $this->eventPublisher = $this->createMock(RefreshTokenEventPublisherInterface::class);
        $this->authTokenFactory = $this->createMock(AuthTokenFactoryInterface::class);
        $this->configureRefreshTokenResponseFactory();
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer(new SharedUuidFactory());
    }

    protected function configureRefreshTokenResponseFactory(): void
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

    protected function expectTokenLookup(
        AuthRefreshToken $token,
        string $plainToken
    ): void {
        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('findByTokenHash')
            ->with(hash('sha256', $plainToken))
            ->willReturn($token);
    }

    protected function expectConsecutiveTokenLookups(
        string $plainToken,
        AuthRefreshToken $first,
        AuthRefreshToken $second
    ): void {
        $this->refreshTokenRepository
            ->expects($this->exactly(2))
            ->method('findByTokenHash')
            ->with(hash('sha256', $plainToken))
            ->willReturnOnConsecutiveCalls($first, $second);
    }

    protected function expectSessionLookup(
        AuthSession $session
    ): void {
        $this->authSessionRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($session);
    }

    protected function expectUserLookup(User $user): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($user);
    }

    protected function expectNeverSessionOrUserLookup(): void
    {
        $this->authSessionRepository
            ->expects($this->never())
            ->method('findById');
        $this->userRepository
            ->expects($this->never())
            ->method('findById');
    }

    protected function expectNeverUserLookup(): void
    {
        $this->userRepository
            ->expects($this->never())
            ->method('findById');
    }

    protected function expectInvalidTokenException(): void
    {
        $this->expectException(
            UnauthorizedHttpException::class
        );
        $this->expectExceptionMessage(
            'Invalid refresh token.'
        );
    }

    /**
     * @param array<string, string|int> $capturedPayload
     */
    protected function expectSuccessfulRotation(
        AuthSession $session,
        User $user,
        string $accessToken,
        array &$capturedPayload
    ): void {
        $this->refreshTokenRepository->expects($this->once())->method('save');
        $this->refreshTokenRepository->expects($this->once())
            ->method('markAsRotatedIfActive')->willReturn(true);
        $this->configureTokenRotationFactories();
        $this->accessTokenGenerator
            ->expects($this->once())
            ->method('generate')
            ->with($this->callback(
                static function (array $p) use (&$capturedPayload): bool {
                    $capturedPayload = $p;
                    return true;
                }
            ))
            ->willReturn($accessToken);
        $this->eventPublisher->expects($this->once())
            ->method('publishRotated')->with($session->getId(), $user->getId());
    }

    protected function expectSuccessfulGraceReuse(
        AuthSession $session,
        User $user,
        string $accessToken
    ): void {
        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('save');
        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('markGraceUsedIfEligible')
            ->willReturn(true);
        $this->configureTokenRotationFactories();
        $this->accessTokenGenerator
            ->expects($this->once())
            ->method('generate')
            ->willReturn($accessToken);
        $this->eventPublisher
            ->expects($this->once())
            ->method('publishRotated')
            ->with($session->getId(), $user->getId());
    }

    protected function expectFailedAtomicRotation(): void
    {
        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('markAsRotatedIfActive')
            ->willReturn(false);
    }

    protected function configureTokenRotationFactories(): void
    {
        $this->authTokenFactory
            ->method('generateOpaqueToken')
            ->willReturn(
                'test-opaque-token-1234567890-abcdefghijklmn'
            );
        $this->configureCreateRefreshTokenCallback();
        $this->configureJwtPayloadFactory();
    }

    protected function configureCreateRefreshTokenCallback(): void
    {
        $this->authTokenFactory
            ->method('createRefreshToken')
            ->willReturnCallback(
                static function (
                    string $sessionId,
                    string $plain,
                    DateTimeImmutable $issuedAt
                ): AuthRefreshToken {
                    return new AuthRefreshToken(
                        (string) new Ulid(),
                        $sessionId,
                        $plain,
                        $issuedAt->modify('+1 month')
                    );
                }
            );
    }

    protected function configureJwtPayloadFactory(): void
    {
        $this->authTokenFactory
            ->method('buildJwtPayload')
            ->willReturnCallback(
                static fn (
                    User $u,
                    string $sid,
                    DateTimeImmutable $iat
                ): array => [
                    'sub' => $u->getId(),
                    'iss' => 'vilnacrm-user-service',
                    'aud' => 'vilnacrm-api',
                    'exp' => $iat->getTimestamp() + 900,
                    'iat' => $iat->getTimestamp(),
                    'nbf' => $iat->getTimestamp(),
                    'jti' => 'test-jti',
                    'sid' => $sid,
                    'roles' => ['ROLE_USER'],
                ]
            );
    }

    protected function invokeHandler(
        string $plainToken
    ): RefreshTokenCommand {
        $handler = $this->createHandler();
        $command = new RefreshTokenCommand($plainToken);
        $handler->__invoke($command);
        return $command;
    }

    /**
     * @param array<string, string|int> $capturedPayload
     */
    protected function assertJwtPayloadContents(
        array $capturedPayload,
        string $userId,
        string $sessionId
    ): void {
        $this->assertSame(
            $userId,
            $capturedPayload['sub']
        );
        $this->assertSame(
            $sessionId,
            $capturedPayload['sid']
        );
        $this->assertIsInt($capturedPayload['iat']);
        $this->assertIsInt($capturedPayload['exp']);
        $this->assertSame(
            900,
            $capturedPayload['exp'] - $capturedPayload['iat']
        );
        $this->assertIsString($capturedPayload['jti']);
        $this->assertSame(
            ['ROLE_USER'],
            $capturedPayload['roles']
        );
    }

    protected function assertOpaqueTokenFormat(
        string $token
    ): void {
        $this->assertSame(43, strlen($token));
        $this->assertStringNotContainsString('=', $token);
        $this->assertMatchesRegularExpression(
            '/^[A-Za-z0-9\-_]+$/',
            $token
        );
    }

    protected function createHandler(
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

    protected function createValidRefreshToken(
        string $plainToken
    ): AuthRefreshToken {
        return new AuthRefreshToken(
            (string) new Ulid(),
            (string) new Ulid(),
            $plainToken,
            new DateTimeImmutable('+1 month')
        );
    }

    protected function createValidSession(
        string $sessionId
    ): AuthSession {
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

    protected function createExpiredSession(
        string $sessionId
    ): AuthSession {
        return new AuthSession(
            $sessionId,
            $this->faker->uuid(),
            '127.0.0.1',
            'Expired Agent',
            new DateTimeImmutable('-2 hours'),
            new DateTimeImmutable('-1 hour'),
            false
        );
    }

    protected function createUser(): User
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
