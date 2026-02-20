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

final class RefreshTokenCommandHandlerTest extends UnitTestCase
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

        $this->refreshTokenRepository = $this->createMock(AuthRefreshTokenRepositoryInterface::class);
        $this->authSessionRepository = $this->createMock(AuthSessionRepositoryInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->accessTokenGenerator = $this->createMock(AccessTokenGeneratorInterface::class);
        $this->eventPublisher = $this->createMock(RefreshTokenEventPublisherInterface::class);
        $this->authTokenFactory = $this->createMock(AuthTokenFactoryInterface::class);
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
            ->expects($this->once())
            ->method('save');

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('markAsRotatedIfActive')
            ->willReturn(true);

        $this->authTokenFactory
            ->method('generateOpaqueToken')
            ->willReturn('test-opaque-token-1234567890-abcdefghijklmn');

        $this->authTokenFactory
            ->method('createRefreshToken')
            ->willReturnCallback(
                static fn (string $sessionId, string $plain, DateTimeImmutable $issuedAt): AuthRefreshToken =>
                    new AuthRefreshToken((string) new Ulid(), $sessionId, $plain, $issuedAt->modify('+1 month'))
            );

        $this->authTokenFactory
            ->method('buildJwtPayload')
            ->willReturnCallback(
                static fn (User $u, string $sid, DateTimeImmutable $iat): array => [
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

        $this->eventPublisher
            ->expects($this->once())
            ->method('publishRotated')
            ->with($session->getId(), $user->getId());

        $handler = $this->createHandler();
        $command = new RefreshTokenCommand($plainToken);
        $handler->__invoke($command);

        $response = $command->getResponse();
        $this->assertSame('new-access-token', $response->getAccessToken());
        $this->assertSame('test-opaque-token-1234567890-abcdefghijklmn', $response->getRefreshToken());
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
            ->expects($this->once())
            ->method('save');

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('markGraceUsedIfEligible')
            ->willReturn(true);

        $this->authTokenFactory
            ->method('generateOpaqueToken')
            ->willReturn('test-opaque-token-1234567890-abcdefghijklmn');

        $this->authTokenFactory
            ->method('createRefreshToken')
            ->willReturnCallback(
                static fn (string $sessionId, string $plain, DateTimeImmutable $issuedAt): AuthRefreshToken =>
                    new AuthRefreshToken((string) new Ulid(), $sessionId, $plain, $issuedAt->modify('+1 month'))
            );

        $this->authTokenFactory
            ->method('buildJwtPayload')
            ->willReturnCallback(
                static fn (User $u, string $sid, DateTimeImmutable $iat): array => [
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
        $this->accessTokenGenerator
            ->expects($this->once())
            ->method('generate')
            ->willReturn('grace-access-token');

        $this->eventPublisher
            ->expects($this->once())
            ->method('publishRotated')
            ->with($session->getId(), $user->getId());

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

    public function testConcurrentRotationUsesLatestRotatedTokenWithinGraceWindow(): void
    {
        $plainToken = 'concurrent-rotated-token';
        $oldToken = $this->createValidRefreshToken($plainToken);
        $latestToken = $this->createValidRefreshToken($plainToken);
        $latestToken->markAsRotated(new DateTimeImmutable('-30 seconds'));
        $session = $this->createValidSession($oldToken->getSessionId());
        $user = $this->createUser();

        $this->refreshTokenRepository
            ->expects($this->exactly(2))
            ->method('findByTokenHash')
            ->with(hash('sha256', $plainToken))
            ->willReturnOnConsecutiveCalls($oldToken, $latestToken);

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('markAsRotatedIfActive')
            ->willReturn(false);

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('markGraceUsedIfEligible')
            ->willReturn(true);

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
            ->expects($this->once())
            ->method('save');

        $this->authTokenFactory
            ->method('generateOpaqueToken')
            ->willReturn('test-opaque-token-1234567890-abcdefghijklmn');

        $this->authTokenFactory
            ->method('createRefreshToken')
            ->willReturnCallback(
                static fn (string $sessionId, string $plain, DateTimeImmutable $issuedAt): AuthRefreshToken =>
                    new AuthRefreshToken((string) new Ulid(), $sessionId, $plain, $issuedAt->modify('+1 month'))
            );

        $this->authTokenFactory
            ->method('buildJwtPayload')
            ->willReturnCallback(
                static fn (User $u, string $sid, DateTimeImmutable $iat): array => [
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
        $this->accessTokenGenerator
            ->expects($this->once())
            ->method('generate')
            ->willReturn('concurrent-access-token');

        $this->eventPublisher
            ->expects($this->once())
            ->method('publishRotated')
            ->with($session->getId(), $user->getId());

        $handler = $this->createHandler();
        $command = new RefreshTokenCommand($plainToken);
        $handler->__invoke($command);

        $this->assertSame(
            'concurrent-access-token',
            $command->getResponse()->getAccessToken()
        );
        $this->assertOpaqueTokenFormat($command->getResponse()->getRefreshToken());
        $this->assertTrue($latestToken->isGraceUsed());
    }

    public function testConcurrentRotationThrowsWhenLatestTokenIsNotRotated(): void
    {
        $plainToken = 'concurrent-not-rotated-token';
        $oldToken = $this->createValidRefreshToken($plainToken);
        $latestToken = $this->createValidRefreshToken($plainToken);
        $session = $this->createValidSession($oldToken->getSessionId());
        $user = $this->createUser();

        $this->refreshTokenRepository
            ->expects($this->exactly(2))
            ->method('findByTokenHash')
            ->with(hash('sha256', $plainToken))
            ->willReturnOnConsecutiveCalls($oldToken, $latestToken);

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('markAsRotatedIfActive')
            ->willReturn(false);

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
            ->expects($this->never())
            ->method('save');

        $this->eventPublisher
            ->expects($this->never())
            ->method('publishRotated');

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid refresh token.');

        $handler = $this->createHandler();
        $handler->__invoke(new RefreshTokenCommand($plainToken));
    }

    public function testTheftDetectedWhenGraceEligibilityCheckFails(): void
    {
        $plainToken = 'grace-eligibility-fails-token';
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
            ->expects($this->once())
            ->method('markGraceUsedIfEligible')
            ->willReturn(false);

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('findBySessionId')
            ->with($session->getId())
            ->willReturn([$token]);

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(
                static fn (AuthRefreshToken $savedToken): bool => $savedToken->isRevoked()
            ));

        $this->authSessionRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(
                static fn (AuthSession $savedSession): bool => $savedSession->isRevoked()
            ));
        $this->eventPublisher
            ->expects($this->once())
            ->method('publishTheftDetected')
            ->with(
                $session->getId(),
                $user->getId(),
                $session->getIpAddress(),
                'double_grace_use'
            );

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid refresh token.');

        $handler = $this->createHandler();
        $handler->__invoke(new RefreshTokenCommand($plainToken));
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
        $this->eventPublisher
            ->expects($this->once())
            ->method('publishTheftDetected')
            ->with(
                $session->getId(),
                $user->getId(),
                $session->getIpAddress(),
                'double_grace_use'
            );

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
        $this->eventPublisher
            ->expects($this->once())
            ->method('publishTheftDetected')
            ->with(
                $session->getId(),
                $user->getId(),
                $session->getIpAddress(),
                'grace_period_expired'
            );

        $this->expectException(UnauthorizedHttpException::class);

        $handler = $this->createHandler();
        $handler->__invoke(new RefreshTokenCommand($plainToken));
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
        $this->eventPublisher
            ->expects($this->once())
            ->method('publishTheftDetected')
            ->with(
                $session->getId(),
                $user->getId(),
                $session->getIpAddress(),
                'double_grace_use'
            );

        $this->expectException(UnauthorizedHttpException::class);

        $handler = $this->createHandler();
        $handler->__invoke(new RefreshTokenCommand($plainToken));
    }

    public function testTheftDetectionSkipsAlreadyRevokedTokens(): void
    {
        $plainToken = 'skip-revoked-token';
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
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(
                static fn (AuthSession $savedSession): bool => $savedSession->isRevoked()
            ));

        $this->refreshTokenRepository
            ->expects($this->never())
            ->method('save');
        $this->eventPublisher
            ->expects($this->once())
            ->method('publishTheftDetected')
            ->with(
                $session->getId(),
                $user->getId(),
                $session->getIpAddress(),
                'double_grace_use'
            );

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
        $this->eventPublisher
            ->expects($this->once())
            ->method('publishTheftDetected')
            ->with(
                $session->getId(),
                $user->getId(),
                $session->getIpAddress(),
                'double_grace_use'
            );

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

    public function testInvokeThrows401WhenSessionIsRevoked(): void
    {
        $plainToken = 'valid-token-revoked-session';
        $token = $this->createValidRefreshToken($plainToken);
        $session = $this->createValidSession($token->getSessionId());
        $session->revoke();

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('findByTokenHash')
            ->willReturn($token);

        $this->authSessionRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($session);

        $this->userRepository
            ->expects($this->never())
            ->method('findById');

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid refresh token.');

        $handler = $this->createHandler();
        $handler->__invoke(new RefreshTokenCommand($plainToken));
    }

    public function testInvokeThrows401WhenSessionIsExpired(): void
    {
        $plainToken = 'valid-token-expired-session';
        $token = $this->createValidRefreshToken($plainToken);
        $session = new AuthSession(
            $token->getSessionId(),
            $this->faker->uuid(),
            '127.0.0.1',
            'Expired Agent',
            new DateTimeImmutable('-2 hours'),
            new DateTimeImmutable('-1 hour'),
            false
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
            ->expects($this->never())
            ->method('findById');

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
            $this->authTokenFactory,
            $this->eventPublisher,
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
