<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Service;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\CommandHandler\PasswordChangeSessionRevoker;
use App\User\Domain\Entity\AuthRefreshToken;
use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Repository\AuthRefreshTokenRepositoryInterface;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use DateTimeImmutable;

final class PasswordChangeSessionRevokerTest extends UnitTestCase
{
    private AuthSessionRepositoryInterface $authSessionRepository;
    private AuthRefreshTokenRepositoryInterface $authRefreshTokenRepository;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->authSessionRepository = $this->createMock(AuthSessionRepositoryInterface::class);
        $this->authRefreshTokenRepository = $this->createMock(AuthRefreshTokenRepositoryInterface::class);
    }

    public function testRevokeOtherSessionsRevokesOnlyNonCurrentSessions(): void
    {
        $userId = $this->faker->uuid();
        $currentSessionId = $this->faker->uuid();
        $otherSessionId = $this->faker->uuid();
        $alreadyRevokedSessionId = $this->faker->uuid();

        $currentSession = $this->createSession($currentSessionId, $userId);
        $otherSession = $this->createSession($otherSessionId, $userId);
        $alreadyRevokedSession = $this->createSession(
            $alreadyRevokedSessionId,
            $userId,
            true
        );

        $activeToken = $this->createRefreshToken($otherSessionId);
        $alreadyRevokedToken = $this->createRefreshToken(
            $otherSessionId,
            true
        );

        $this->authSessionRepository
            ->expects($this->once())
            ->method('findByUserId')
            ->with($userId)
            ->willReturn([$currentSession, $otherSession, $alreadyRevokedSession]);
        $this->authSessionRepository
            ->expects($this->once())
            ->method('save')
            ->with($otherSession);

        $this->authRefreshTokenRepository
            ->expects($this->once())
            ->method('findBySessionId')
            ->with($otherSessionId)
            ->willReturn([$activeToken, $alreadyRevokedToken]);
        $this->authRefreshTokenRepository
            ->expects($this->once())
            ->method('save')
            ->with($activeToken);

        $revokedCount = $this->createRevoker()->revokeOtherSessions(
            $userId,
            $currentSessionId
        );

        $this->assertSame(1, $revokedCount);
        $this->assertFalse($currentSession->isRevoked());
        $this->assertTrue($otherSession->isRevoked());
        $this->assertTrue($alreadyRevokedSession->isRevoked());
        $this->assertTrue($activeToken->isRevoked());
        $this->assertTrue($alreadyRevokedToken->isRevoked());
    }

    public function testRevokeOtherSessionsReturnsZeroWhenNothingToRevoke(): void
    {
        $userId = $this->faker->uuid();
        $currentSessionId = $this->faker->uuid();

        $currentSession = $this->createSession($currentSessionId, $userId);
        $alreadyRevokedSession = $this->createSession(
            $this->faker->uuid(),
            $userId,
            true
        );

        $this->authSessionRepository
            ->expects($this->once())
            ->method('findByUserId')
            ->with($userId)
            ->willReturn([$currentSession, $alreadyRevokedSession]);
        $this->authSessionRepository
            ->expects($this->never())
            ->method('save');
        $this->authRefreshTokenRepository
            ->expects($this->never())
            ->method('findBySessionId');
        $this->authRefreshTokenRepository
            ->expects($this->never())
            ->method('save');

        $revokedCount = $this->createRevoker()->revokeOtherSessions(
            $userId,
            $currentSessionId
        );

        $this->assertSame(0, $revokedCount);
        $this->assertFalse($currentSession->isRevoked());
        $this->assertTrue($alreadyRevokedSession->isRevoked());
    }

    public function testRevokeOtherSessionsSkipsRevokedTokenAndRevokesLaterActiveToken(): void
    {
        $userId = $this->faker->uuid();
        $currentSessionId = $this->faker->uuid();
        $otherSessionId = $this->faker->uuid();

        $otherSession = $this->createSession($otherSessionId, $userId);
        $revokedToken = $this->createRefreshToken($otherSessionId, true);
        $activeToken = $this->createRefreshToken($otherSessionId);

        $this->authSessionRepository
            ->expects($this->once())
            ->method('findByUserId')
            ->with($userId)
            ->willReturn([
                $this->createSession($currentSessionId, $userId),
                $otherSession,
            ]);
        $this->authSessionRepository
            ->expects($this->once())
            ->method('save')
            ->with($otherSession);

        $this->authRefreshTokenRepository
            ->expects($this->once())
            ->method('findBySessionId')
            ->with($otherSessionId)
            ->willReturn([$revokedToken, $activeToken]);
        $this->authRefreshTokenRepository
            ->expects($this->once())
            ->method('save')
            ->with($activeToken);

        $revokedCount = $this->createRevoker()->revokeOtherSessions(
            $userId,
            $currentSessionId
        );

        $this->assertSame(1, $revokedCount);
        $this->assertTrue($otherSession->isRevoked());
        $this->assertTrue($revokedToken->isRevoked());
        $this->assertTrue($activeToken->isRevoked());
    }

    /** @SuppressWarnings(PHPMD.BooleanArgumentFlag) */
    private function createSession(
        string $sessionId,
        string $userId,
        bool $revoked = false
    ): AuthSession {
        $session = new AuthSession(
            $sessionId,
            $userId,
            $this->faker->ipv4(),
            $this->faker->userAgent(),
            new DateTimeImmutable('-10 minutes'),
            new DateTimeImmutable('+50 minutes'),
            false
        );

        if ($revoked) {
            $session->revoke();
        }

        return $session;
    }

    /** @SuppressWarnings(PHPMD.BooleanArgumentFlag) */
    private function createRefreshToken(
        string $sessionId,
        bool $revoked = false
    ): AuthRefreshToken {
        $refreshToken = new AuthRefreshToken(
            $this->faker->uuid(),
            $sessionId,
            $this->faker->sha256(),
            new DateTimeImmutable('+1 day')
        );

        if ($revoked) {
            $refreshToken->revoke();
        }

        return $refreshToken;
    }

    private function createRevoker(): PasswordChangeSessionRevoker
    {
        return new PasswordChangeSessionRevoker(
            $this->authSessionRepository,
            $this->authRefreshTokenRepository
        );
    }
}
