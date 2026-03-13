<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Revoker;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Processor\EventPublisher\SessionEventsInterface;
use App\User\Application\Processor\Revoker\AllSessionsRevoker;
use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Repository\AuthRefreshTokenRepositoryInterface;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;

final class AllSessionsRevokerTest extends UnitTestCase
{
    private AuthSessionRepositoryInterface&MockObject $sessionRepository;
    private AuthRefreshTokenRepositoryInterface&MockObject $refreshTokenRepository;
    private SessionEventsInterface&MockObject $sessionEvents;
    private AllSessionsRevoker $revoker;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->sessionRepository = $this->createMock(
            AuthSessionRepositoryInterface::class
        );
        $this->refreshTokenRepository = $this->createMock(
            AuthRefreshTokenRepositoryInterface::class
        );
        $this->sessionEvents = $this->createMock(
            SessionEventsInterface::class
        );
        $this->revoker = new AllSessionsRevoker(
            $this->sessionRepository,
            $this->refreshTokenRepository,
            $this->sessionEvents
        );
    }

    public function testRevokeAllSessionsRevokesAllActiveSessionsAndTokens(): void
    {
        $userId = $this->faker->uuid();
        $sessionOne = $this->createActiveSession($this->faker->uuid());
        $sessionTwo = $this->createActiveSession($this->faker->uuid());

        $this->expectFindSessions($userId, [$sessionOne, $sessionTwo]);
        $this->sessionRepository->expects($this->exactly(2))
            ->method('save');
        $this->refreshTokenRepository->expects($this->exactly(2))
            ->method('revokeBySessionId');
        $this->expectRevokedEvent($userId, 'password_reset', 2);

        $revokedCount = $this->revoker->revokeAllSessions(
            $userId,
            'password_reset'
        );

        self::assertSame(2, $revokedCount);
        self::assertTrue($sessionOne->isRevoked());
        self::assertTrue($sessionTwo->isRevoked());
    }

    public function testRevokeAllSessionsSkipsAlreadyRevokedSessions(): void
    {
        $userId = $this->faker->uuid();
        $revokedSessionId = $this->faker->uuid();
        $revokedSession = $this->createRevokedSession($revokedSessionId);

        $this->expectFindSessions($userId, [$revokedSession]);

        $this->refreshTokenRepository->expects($this->once())
            ->method('revokeBySessionId')
            ->with($revokedSessionId);

        $this->sessionRepository->expects($this->never())
            ->method('save');

        $this->expectRevokedEvent($userId, 'user_initiated', 0);

        $revokedCount = $this->revoker->revokeAllSessions(
            $userId,
            'user_initiated'
        );

        self::assertSame(0, $revokedCount);
    }

    public function testRevokeAllSessionsContinuesAfterRevokedSession(): void
    {
        $userId = $this->faker->uuid();
        $revokedSession = $this->createRevokedSession($this->faker->uuid());
        $activeSession = $this->createActiveSession($this->faker->uuid());

        $this->arrangeRevokedThenActive($userId, $revokedSession, $activeSession);

        $revokedCount = $this->revoker->revokeAllSessions($userId, 'user_initiated');

        self::assertSame(1, $revokedCount);
        self::assertTrue($activeSession->isRevoked());
    }

    public function testRevokeAllSessionsStillRevokesRefreshTokensForRevokedSessions(): void
    {
        $userId = $this->faker->uuid();
        [$activeSession, $expectedSessionIds] = $this->prepareMixedSessionScenario(
            $userId
        );

        $capturedSessionIds = [];
        $this->expectRefreshTokenRevocation(2, $capturedSessionIds);
        $this->expectRevokedEvent($userId, 'user_initiated', 1);

        $revokedCount = $this->revoker->revokeAllSessions(
            $userId,
            'user_initiated'
        );

        $this->assertMixedSessionRevocationResult(
            $activeSession,
            $capturedSessionIds,
            $expectedSessionIds,
            $revokedCount
        );
    }

    private function arrangeRevokedThenActive(
        string $userId,
        AuthSession $revokedSession,
        AuthSession $activeSession
    ): void {
        $this->expectFindSessions($userId, [$revokedSession, $activeSession]);
        $this->sessionRepository->expects($this->once())
            ->method('save')->with($activeSession);
        $this->refreshTokenRepository->expects($this->exactly(2))
            ->method('revokeBySessionId');
        $this->expectRevokedEvent($userId, 'user_initiated', 1);
    }

    private function createActiveSession(string $sessionId): AuthSession
    {
        return new AuthSession(
            $sessionId,
            $this->faker->uuid(),
            $this->faker->ipv4(),
            'TestAgent/1.0',
            new DateTimeImmutable(),
            new DateTimeImmutable('+1 hour'),
            false
        );
    }

    private function createRevokedSession(string $sessionId): AuthSession
    {
        $session = $this->createActiveSession($sessionId);
        $session->revoke();

        return $session;
    }

    /**
     * @return array{AuthSession, array<string>}
     */
    private function prepareMixedSessionScenario(string $userId): array
    {
        $activeSessionId = $this->faker->uuid();
        $revokedSessionId = $this->faker->uuid();
        $activeSession = $this->createActiveSession($activeSessionId);
        $revokedSession = $this->createRevokedSession($revokedSessionId);

        $this->expectFindSessions($userId, [$activeSession, $revokedSession]);
        $this->sessionRepository->expects($this->once())
            ->method('save')
            ->with($activeSession);

        return [$activeSession, [$activeSessionId, $revokedSessionId]];
    }

    /**
     * @param array<string> $capturedSessionIds
     * @param array<string> $expectedSessionIds
     */
    private function assertMixedSessionRevocationResult(
        AuthSession $activeSession,
        array $capturedSessionIds,
        array $expectedSessionIds,
        int $revokedCount
    ): void {
        self::assertSame(1, $revokedCount);
        self::assertTrue($activeSession->isRevoked());
        self::assertSame($expectedSessionIds, $capturedSessionIds);
    }

    /**
     * @param array<AuthSession> $sessions
     */
    private function expectFindSessions(string $userId, array $sessions): void
    {
        $this->sessionRepository->expects($this->once())
            ->method('findByUserId')
            ->with($userId)
            ->willReturn($sessions);
    }

    private function expectRevokedEvent(
        string $userId,
        string $reason,
        int $count
    ): void {
        $this->sessionEvents->expects($this->once())
            ->method('publishAllSessionsRevoked')
            ->with($userId, $reason, $count);
    }

    /**
     * @param array<string> $capturedSessionIds
     */
    private function expectRefreshTokenRevocation(
        int $count,
        array &$capturedSessionIds
    ): void {
        $this->refreshTokenRepository->expects($this->exactly($count))
            ->method('revokeBySessionId')
            ->willReturnCallback(
                static function (
                    string $sessionId
                ) use (&$capturedSessionIds): void {
                    $capturedSessionIds[] = $sessionId;
                }
            );
    }
}
