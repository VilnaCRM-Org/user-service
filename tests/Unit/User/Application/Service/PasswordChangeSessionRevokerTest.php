<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Service;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Service\PasswordChangeSessionRevoker;
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
        $this->authRefreshTokenRepository = $this->createMock(
            AuthRefreshTokenRepositoryInterface::class
        );
    }

    public function testRevokeOtherSessionsRevokesOnlyNonCurrentSessions(): void
    {
        $userId = $this->faker->uuid();
        $currentSessionId = $this->faker->uuid();
        $otherSessionId = $this->faker->uuid();
        $currentSession = $this->createSession($currentSessionId, $userId);
        $otherSession = $this->createSession($otherSessionId, $userId);
        $revokedSession = $this->createRevokedSession($this->faker->uuid(), $userId);
        $activeToken = $this->createRefreshToken($otherSessionId);
        $revokedToken = $this->createRevokedRefreshToken($otherSessionId);
        $allSessions = [$currentSession, $otherSession, $revokedSession];
        $this->expectSessionRevocation($userId, $allSessions, $otherSession);
        $this->expectTokenRevocation($otherSessionId, [$activeToken, $revokedToken], $activeToken);
        $count = $this->createRevoker()->revokeOtherSessions($userId, $currentSessionId);
        $this->assertSame(1, $count);
        $this->assertFalse($currentSession->isRevoked());
        $this->assertTrue($otherSession->isRevoked());
        $this->assertTrue($revokedSession->isRevoked());
        $this->assertTrue($activeToken->isRevoked());
        $this->assertTrue($revokedToken->isRevoked());
    }

    public function testRevokeOtherSessionsReturnsZeroWhenNothingToRevoke(): void
    {
        $userId = $this->faker->uuid();
        $currentSessionId = $this->faker->uuid();
        $currentSession = $this->createSession($currentSessionId, $userId);
        $revokedSession = $this->createRevokedSession($this->faker->uuid(), $userId);
        $this->expectNoRevocation($userId, [$currentSession, $revokedSession]);
        $count = $this->createRevoker()->revokeOtherSessions($userId, $currentSessionId);
        $this->assertSame(0, $count);
        $this->assertFalse($currentSession->isRevoked());
        $this->assertTrue($revokedSession->isRevoked());
    }

    public function testRevokeOtherSessionsSkipsRevokedTokenAndRevokesLaterActiveToken(): void
    {
        $userId = $this->faker->uuid();
        $currentSessionId = $this->faker->uuid();
        $otherSessionId = $this->faker->uuid();
        $currentSession = $this->createSession($currentSessionId, $userId);
        $otherSession = $this->createSession($otherSessionId, $userId);
        $revokedToken = $this->createRevokedRefreshToken($otherSessionId);
        $activeToken = $this->createRefreshToken($otherSessionId);
        $this->expectSessionRevocation($userId, [$currentSession, $otherSession], $otherSession);
        $this->expectTokenRevocation($otherSessionId, [$revokedToken, $activeToken], $activeToken);
        $count = $this->createRevoker()->revokeOtherSessions($userId, $currentSessionId);
        $this->assertSame(1, $count);
        $this->assertTrue($otherSession->isRevoked());
        $this->assertTrue($revokedToken->isRevoked());
        $this->assertTrue($activeToken->isRevoked());
    }

    /**
     * @param array<AuthSession> $sessions
     */
    private function expectSessionRevocation(
        string $userId,
        array $sessions,
        AuthSession $savedSession
    ): void {
        $this->authSessionRepository
            ->expects($this->once())
            ->method('findByUserId')
            ->with($userId)
            ->willReturn($sessions);
        $this->authSessionRepository
            ->expects($this->once())
            ->method('save')
            ->with($savedSession);
    }

    /**
     * @param array<AuthRefreshToken> $tokens
     */
    private function expectTokenRevocation(
        string $sessionId,
        array $tokens,
        AuthRefreshToken $savedToken
    ): void {
        $this->authRefreshTokenRepository
            ->expects($this->once())
            ->method('findBySessionId')
            ->with($sessionId)
            ->willReturn($tokens);
        $this->authRefreshTokenRepository
            ->expects($this->once())
            ->method('save')
            ->with($savedToken);
    }

    /**
     * @param array<AuthSession> $sessions
     */
    private function expectNoRevocation(string $userId, array $sessions): void
    {
        $this->authSessionRepository
            ->expects($this->once())
            ->method('findByUserId')
            ->with($userId)
            ->willReturn($sessions);
        $this->authSessionRepository->expects($this->never())->method('save');
        $this->authRefreshTokenRepository->expects($this->never())->method('findBySessionId');
        $this->authRefreshTokenRepository->expects($this->never())->method('save');
    }

    private function createSession(
        string $sessionId,
        string $userId
    ): AuthSession {
        return new AuthSession(
            $sessionId,
            $userId,
            $this->faker->ipv4(),
            $this->faker->userAgent(),
            new DateTimeImmutable('-10 minutes'),
            new DateTimeImmutable('+50 minutes'),
            false
        );
    }

    private function createRevokedSession(
        string $sessionId,
        string $userId
    ): AuthSession {
        $session = $this->createSession($sessionId, $userId);
        $session->revoke();

        return $session;
    }

    private function createRefreshToken(string $sessionId): AuthRefreshToken
    {
        return new AuthRefreshToken(
            $this->faker->uuid(),
            $sessionId,
            $this->faker->sha256(),
            new DateTimeImmutable('+1 day')
        );
    }

    private function createRevokedRefreshToken(string $sessionId): AuthRefreshToken
    {
        $refreshToken = $this->createRefreshToken($sessionId);
        $refreshToken->revoke();

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
