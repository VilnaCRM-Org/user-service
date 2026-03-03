<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\SignOutAllCommand;
use App\User\Application\CommandHandler\SignOutAllCommandHandler;
use App\User\Application\EventPublisher\SessionEventsInterface;
use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Repository\AuthRefreshTokenRepositoryInterface;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;

final class SignOutAllCommandHandlerTest extends UnitTestCase
{
    private AuthSessionRepositoryInterface&MockObject $sessionRepository;
    private AuthRefreshTokenRepositoryInterface&MockObject $refreshTokenRepository;
    private SessionEventsInterface&MockObject $sessionEvents;
    private SignOutAllCommandHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->sessionRepository = $this->createMock(AuthSessionRepositoryInterface::class);
        $this->refreshTokenRepository =
            $this->createMock(AuthRefreshTokenRepositoryInterface::class);
        $this->sessionEvents = $this->createMock(SessionEventsInterface::class);
        $this->handler = new SignOutAllCommandHandler(
            $this->sessionRepository,
            $this->refreshTokenRepository,
            $this->sessionEvents
        );
    }

    public function testInvokeRevokesAllSessionsAndTokens(): void
    {
        $userId = $this->faker->uuid();
        $session1 = $this->createActiveSession($this->faker->uuid());
        $session2 = $this->createActiveSession($this->faker->uuid());
        $this->expectFindSessions($userId, [$session1, $session2]);
        $this->sessionRepository->expects($this->exactly(2))->method('save');
        $this->refreshTokenRepository->expects($this->exactly(2))->method('revokeBySessionId');
        $this->expectRevokedEvent($userId, 'user_initiated', 2);

        $this->handler->__invoke(new SignOutAllCommand($userId));

        self::assertTrue($session1->isRevoked());
        self::assertTrue($session2->isRevoked());
    }

    public function testInvokeRevokesRefreshTokensForAlreadyRevokedSessions(): void
    {
        $userId = $this->faker->uuid();
        $activeSessionId = $this->faker->uuid();
        $revokedSessionId = $this->faker->uuid();
        $activeSession = $this->createActiveSession($activeSessionId);
        $revokedSession = $this->createRevokedSession($revokedSessionId);
        $this->expectFindSessions($userId, [$activeSession, $revokedSession]);
        $this->sessionRepository->expects($this->once())->method('save')->with($activeSession);
        $capturedIds = [];
        $this->expectRefreshTokenRevocation(2, $capturedIds);
        $this->expectRevokedEvent($userId, 'user_initiated', 1);

        $this->handler->__invoke(new SignOutAllCommand($userId));

        self::assertTrue($activeSession->isRevoked());
        self::assertSame([$activeSessionId, $revokedSessionId], $capturedIds);
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
        $session = new AuthSession(
            $sessionId,
            $this->faker->uuid(),
            $this->faker->ipv4(),
            'TestAgent/1.0',
            new DateTimeImmutable(),
            new DateTimeImmutable('+1 hour'),
            false
        );
        $session->revoke();

        return $session;
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
     * @param array<string> $capturedIds
     */
    private function expectRefreshTokenRevocation(
        int $count,
        array &$capturedIds
    ): void {
        $this->refreshTokenRepository->expects($this->exactly($count))
            ->method('revokeBySessionId')
            ->willReturnCallback(
                static function (string $sessionId) use (&$capturedIds): void {
                    $capturedIds[] = $sessionId;
                }
            );
    }
}
