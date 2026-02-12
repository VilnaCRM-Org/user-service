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

final class RefreshTokenCommandHandlerTest extends UnitTestCase
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

        $this->refreshTokenRepository = $this->createMock(AuthRefreshTokenRepositoryInterface::class);
        $this->authSessionRepository = $this->createMock(AuthSessionRepositoryInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->accessTokenGenerator = $this->createMock(AccessTokenGeneratorInterface::class);
        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->uuidFactory = $this->createMock(UuidFactory::class);
        $this->ulidFactory = $this->createMock(UlidFactory::class);
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer(new SharedUuidFactory());
    }

    public function testInvokeRotatesTokenAndIssuesNewTokens(): void
    {
        $plainToken = 'valid-refresh-token-value';
        $oldToken = $this->createValidRefreshToken($plainToken);
        $session = $this->createValidSession($oldToken->getSessionId());
        $user = $this->createUser();

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('findByTokenHash')
            ->with(hash('sha256', $plainToken))
            ->willReturn($oldToken);

        $this->authSessionRepository
            ->expects($this->once())
            ->method('findById')
            ->with($oldToken->getSessionId())
            ->willReturn($session);

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->with($session->getUserId())
            ->willReturn($user);

        $this->refreshTokenRepository
            ->expects($this->exactly(2))
            ->method('save');

        $newTokenId = new Ulid();
        $this->ulidFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($newTokenId);

        $this->uuidFactory
            ->method('create')
            ->willReturnCallback(static fn () => Uuid::v4());

        $this->accessTokenGenerator
            ->expects($this->once())
            ->method('generate')
            ->with($this->callback(
                static fn (array $payload): bool => $payload['sub'] === $user->getId()
                    && $payload['sid'] === $session->getId()
                    && is_int($payload['iat'] ?? null)
                    && is_int($payload['exp'] ?? null)
                    && ($payload['exp'] - $payload['iat']) === 900
                    && is_string($payload['jti'] ?? null)
                    && $payload['roles'] === ['ROLE_USER']
            ))
            ->willReturn('new-access-token');

        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->with($this->callback(
                static fn (RefreshTokenRotatedEvent $e): bool => $e->sessionId === $session->getId()
                    && $e->userId === $user->getId()
            ));

        $handler = $this->createHandler();
        $command = new RefreshTokenCommand($plainToken);
        $handler->__invoke($command);

        $response = $command->getResponse();
        $this->assertSame('new-access-token', $response->getAccessToken());
        $this->assertNotEmpty($response->getRefreshToken());
        $this->assertOpaqueTokenFormat($response->getRefreshToken());
        $this->assertTrue($oldToken->isRotated());
    }

    public function testInvokeThrows401WhenTokenNotFound(): void
    {
        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('findByTokenHash')
            ->willReturn(null);

        $this->authSessionRepository
            ->expects($this->never())
            ->method('findById');

        $this->userRepository
            ->expects($this->never())
            ->method('findById');

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid refresh token.');

        $handler = $this->createHandler();
        $handler->__invoke(new RefreshTokenCommand('invalid-token'));
    }

    public function testInvokeThrows401WhenTokenIsExpired(): void
    {
        $plainToken = 'expired-token';
        $token = new AuthRefreshToken(
            (string) new Ulid(),
            (string) new Ulid(),
            $plainToken,
            new DateTimeImmutable('-1 hour')
        );

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('findByTokenHash')
            ->with(hash('sha256', $plainToken))
            ->willReturn($token);

        $this->authSessionRepository
            ->expects($this->never())
            ->method('findById');

        $this->userRepository
            ->expects($this->never())
            ->method('findById');

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid refresh token.');

        $handler = $this->createHandler();
        $handler->__invoke(new RefreshTokenCommand($plainToken));
    }

    public function testInvokeThrows401WhenTokenIsRevoked(): void
    {
        $plainToken = 'revoked-token';
        $token = $this->createValidRefreshToken($plainToken);
        $token->revoke();

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('findByTokenHash')
            ->with(hash('sha256', $plainToken))
            ->willReturn($token);

        $this->authSessionRepository
            ->expects($this->never())
            ->method('findById');

        $this->userRepository
            ->expects($this->never())
            ->method('findById');

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid refresh token.');

        $handler = $this->createHandler();
        $handler->__invoke(new RefreshTokenCommand($plainToken));
    }

    public function testGraceReuseSucceedsWithinWindow(): void
    {
        $plainToken = 'rotated-token';
        $token = $this->createValidRefreshToken($plainToken);
        $token->markAsRotated(new DateTimeImmutable('-30 seconds'));
        $session = $this->createValidSession($token->getSessionId());
        $user = $this->createUser();

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('findByTokenHash')
            ->willReturn($token);

        $this->authSessionRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($session);

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($user);

        $this->refreshTokenRepository
            ->expects($this->exactly(2))
            ->method('save');

        $this->ulidFactory
            ->method('create')
            ->willReturnCallback(static fn () => new Ulid());

        $this->uuidFactory
            ->method('create')
            ->willReturnCallback(static fn () => Uuid::v4());

        $this->accessTokenGenerator
            ->expects($this->once())
            ->method('generate')
            ->willReturn('grace-access-token');

        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(RefreshTokenRotatedEvent::class));

        $handler = $this->createHandler();
        $command = new RefreshTokenCommand($plainToken);
        $handler->__invoke($command);

        $this->assertSame(
            'grace-access-token',
            $command->getResponse()->getAccessToken()
        );
        $this->assertOpaqueTokenFormat($command->getResponse()->getRefreshToken());
        $this->assertTrue($token->isGraceUsed());
    }

    public function testTheftDetectedWhenGraceUsedTwice(): void
    {
        $plainToken = 'double-grace-token';
        $token = $this->createValidRefreshToken($plainToken);
        $token->markAsRotated(new DateTimeImmutable('-30 seconds'));
        $token->markGraceUsed();
        $session = $this->createValidSession($token->getSessionId());
        $user = $this->createUser();

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('findByTokenHash')
            ->willReturn($token);

        $this->authSessionRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($session);

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($user);

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('findBySessionId')
            ->with($session->getId())
            ->willReturn([$token]);

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(
                static fn (AuthRefreshToken $t): bool => $t->isRevoked()
            ));

        $this->authSessionRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(
                static fn (AuthSession $s): bool => $s->isRevoked()
            ));

        $this->uuidFactory
            ->method('create')
            ->willReturnCallback(static fn () => Uuid::v4());

        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->with($this->callback(
                static fn (RefreshTokenTheftDetectedEvent $e): bool => $e->reason === 'double_grace_use'
                    && $e->ipAddress === $session->getIpAddress()
            ));

        $this->expectException(UnauthorizedHttpException::class);

        $handler = $this->createHandler();
        $handler->__invoke(new RefreshTokenCommand($plainToken));
    }

    public function testTheftDetectedWhenGracePeriodExpired(): void
    {
        $plainToken = 'post-grace-token';
        $token = $this->createValidRefreshToken($plainToken);
        $token->markAsRotated(new DateTimeImmutable('-120 seconds'));
        $session = $this->createValidSession($token->getSessionId());
        $user = $this->createUser();

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('findByTokenHash')
            ->willReturn($token);

        $this->authSessionRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($session);

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($user);

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('findBySessionId')
            ->with($session->getId())
            ->willReturn([$token]);

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(
                static fn (AuthRefreshToken $t): bool => $t->isRevoked()
            ));

        $this->authSessionRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(
                static fn (AuthSession $s): bool => $s->isRevoked()
            ));

        $this->uuidFactory
            ->method('create')
            ->willReturnCallback(static fn () => Uuid::v4());

        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->with($this->callback(
                static fn (RefreshTokenTheftDetectedEvent $e): bool => $e->reason === 'grace_period_expired'
                    && $e->ipAddress === $session->getIpAddress()
            ));

        $this->expectException(UnauthorizedHttpException::class);

        $handler = $this->createHandler();
        $handler->__invoke(new RefreshTokenCommand($plainToken));
    }

    public function testGraceWindowCanBeConfigured(): void
    {
        $plainToken = 'custom-grace-window-token';
        $token = $this->createValidRefreshToken($plainToken);
        $token->markAsRotated(new DateTimeImmutable('-120 seconds'));
        $session = $this->createValidSession($token->getSessionId());
        $user = $this->createUser();

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('findByTokenHash')
            ->willReturn($token);

        $this->authSessionRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($session);

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($user);

        $this->refreshTokenRepository
            ->expects($this->exactly(2))
            ->method('save');

        $this->ulidFactory
            ->method('create')
            ->willReturnCallback(static fn () => new Ulid());

        $this->uuidFactory
            ->method('create')
            ->willReturnCallback(static fn () => Uuid::v4());

        $this->accessTokenGenerator
            ->expects($this->once())
            ->method('generate')
            ->willReturn('custom-grace-token');

        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(RefreshTokenRotatedEvent::class));

        $handler = $this->createHandler(180);
        $command = new RefreshTokenCommand($plainToken);
        $handler->__invoke($command);

        $this->assertSame(
            'custom-grace-token',
            $command->getResponse()->getAccessToken()
        );
        $this->assertOpaqueTokenFormat($command->getResponse()->getRefreshToken());
        $this->assertTrue($token->isGraceUsed());
    }

    public function testTheftDetectionUsesOldTokenWhenSessionLookupReturnsEmpty(): void
    {
        $plainToken = 'fallback-old-token';
        $token = $this->createValidRefreshToken($plainToken);
        $token->markAsRotated(new DateTimeImmutable('-30 seconds'));
        $token->markGraceUsed();
        $session = $this->createValidSession($token->getSessionId());
        $user = $this->createUser();

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('findByTokenHash')
            ->willReturn($token);

        $this->authSessionRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($session);

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($user);

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('findBySessionId')
            ->with($session->getId())
            ->willReturn([]);

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(
                static fn (AuthRefreshToken $t): bool => $t->isRevoked()
            ));

        $this->authSessionRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(
                static fn (AuthSession $s): bool => $s->isRevoked()
            ));

        $this->uuidFactory
            ->method('create')
            ->willReturnCallback(static fn () => Uuid::v4());

        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(RefreshTokenTheftDetectedEvent::class));

        $this->expectException(UnauthorizedHttpException::class);

        $handler = $this->createHandler();
        $handler->__invoke(new RefreshTokenCommand($plainToken));
    }

    public function testTheftDetectionSkipsAlreadyRevokedSessionAndTokens(): void
    {
        $plainToken = 'skip-revoked-token';
        $token = $this->createValidRefreshToken($plainToken);
        $token->markAsRotated(new DateTimeImmutable('-30 seconds'));
        $token->markGraceUsed();
        $session = $this->createValidSession($token->getSessionId());
        $session->revoke();
        $user = $this->createUser();

        $alreadyRevokedToken = new AuthRefreshToken(
            (string) new Ulid(),
            $session->getId(),
            'already-revoked-token',
            new DateTimeImmutable('+1 month')
        );
        $alreadyRevokedToken->revoke();

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('findByTokenHash')
            ->willReturn($token);

        $this->authSessionRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($session);

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($user);

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('findBySessionId')
            ->with($session->getId())
            ->willReturn([$alreadyRevokedToken]);

        $this->authSessionRepository
            ->expects($this->never())
            ->method('save');

        $this->refreshTokenRepository
            ->expects($this->never())
            ->method('save');

        $this->uuidFactory
            ->method('create')
            ->willReturnCallback(static fn () => Uuid::v4());

        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(RefreshTokenTheftDetectedEvent::class));

        $this->expectException(UnauthorizedHttpException::class);

        $handler = $this->createHandler();
        $handler->__invoke(new RefreshTokenCommand($plainToken));
    }

    public function testTheftDetectionRevokesLaterActiveTokenAfterRevokedToken(): void
    {
        $plainToken = 'revoked-then-active-token';
        $token = $this->createValidRefreshToken($plainToken);
        $token->markAsRotated(new DateTimeImmutable('-30 seconds'));
        $token->markGraceUsed();
        $session = $this->createValidSession($token->getSessionId());
        $user = $this->createUser();

        $alreadyRevokedToken = new AuthRefreshToken(
            (string) new Ulid(),
            $session->getId(),
            'already-revoked-token',
            new DateTimeImmutable('+1 month')
        );
        $alreadyRevokedToken->revoke();

        $activeToken = new AuthRefreshToken(
            (string) new Ulid(),
            $session->getId(),
            'active-token',
            new DateTimeImmutable('+1 month')
        );

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('findByTokenHash')
            ->willReturn($token);

        $this->authSessionRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($session);

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($user);

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('findBySessionId')
            ->with($session->getId())
            ->willReturn([$alreadyRevokedToken, $activeToken]);

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(
                static fn (AuthRefreshToken $savedToken): bool => $savedToken->getId() === $activeToken->getId()
                    && $savedToken->isRevoked()
            ));

        $this->authSessionRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(
                static fn (AuthSession $savedSession): bool => $savedSession->isRevoked()
            ));

        $this->uuidFactory
            ->method('create')
            ->willReturnCallback(static fn () => Uuid::v4());

        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(RefreshTokenTheftDetectedEvent::class));

        $this->expectException(UnauthorizedHttpException::class);

        $handler = $this->createHandler();
        $handler->__invoke(new RefreshTokenCommand($plainToken));
    }

    public function testInvokeThrows401WhenSessionNotFound(): void
    {
        $plainToken = 'valid-token';
        $token = $this->createValidRefreshToken($plainToken);

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('findByTokenHash')
            ->willReturn($token);

        $this->authSessionRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn(null);

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid refresh token.');

        $handler = $this->createHandler();
        $handler->__invoke(new RefreshTokenCommand($plainToken));
    }

    public function testInvokeThrows401WhenUserNotFound(): void
    {
        $plainToken = 'valid-token-user-missing';
        $token = $this->createValidRefreshToken($plainToken);
        $session = $this->createValidSession($token->getSessionId());

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('findByTokenHash')
            ->willReturn($token);

        $this->authSessionRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($session);

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn(null);

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid refresh token.');

        $handler = $this->createHandler();
        $handler->__invoke(new RefreshTokenCommand($plainToken));
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

    private function createValidRefreshToken(
        string $plainToken
    ): AuthRefreshToken {
        return new AuthRefreshToken(
            (string) new Ulid(),
            (string) new Ulid(),
            $plainToken,
            new DateTimeImmutable('+1 month')
        );
    }

    private function createValidSession(
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
