<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\SignOutAllCommand;
use App\User\Application\CommandHandler\SignOutAllCommandHandler;
use App\User\Domain\Collection\AuthSessionCollection;
use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Repository\AuthRefreshTokenRepositoryInterface;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use App\User\Infrastructure\Publisher\SessionPublisherInterface;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;

final class SignOutAllCommandHandlerTest extends UnitTestCase
{
    private AuthSessionRepositoryInterface&MockObject $sessionRepository;
    private AuthRefreshTokenRepositoryInterface&MockObject $refreshTokenRepository;
    private SessionPublisherInterface&MockObject $sessionEvents;
    private SignOutAllCommandHandler $handler;
    /** @var list<string> */
    private array $savedSessionIds = [];

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->sessionRepository = $this->createMock(AuthSessionRepositoryInterface::class);
        $this->refreshTokenRepository = $this->createMock(
            AuthRefreshTokenRepositoryInterface::class
        );
        $this->sessionEvents = $this->createMock(SessionPublisherInterface::class);
        $this->handler = new SignOutAllCommandHandler(
            $this->sessionRepository,
            $this->refreshTokenRepository,
            $this->sessionEvents
        );
        $this->savedSessionIds = [];
    }

    public function testInvokeRevokesSessionsAndPublishesAllSessionsRevokedEvent(): void
    {
        $userId = $this->faker->uuid();
        [$activeSessionId, $secondActiveSessionId] = $this->configureRevocationScenario($userId);

        $this->sessionEvents->expects($this->once())
            ->method('publishAllSessionsRevoked')
            ->with($userId, 'user_initiated', 2);

        $this->handler->__invoke(new SignOutAllCommand($userId));

        self::assertEqualsCanonicalizing(
            [$activeSessionId, $secondActiveSessionId],
            $this->savedSessionIds
        );
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function configureRevocationScenario(string $userId): array
    {
        $activeSession = $this->createSession($this->faker->uuid(), $userId);
        $alreadyRevokedSession = $this->createSession($this->faker->uuid(), $userId);
        $alreadyRevokedSession->revoke();
        $secondActiveSession = $this->createSession($this->faker->uuid(), $userId);
        $sessions = new AuthSessionCollection(
            $activeSession,
            $alreadyRevokedSession,
            $secondActiveSession
        );
        $this->sessionRepository->expects($this->once())
            ->method('findByUserId')
            ->with($userId)
            ->willReturn($sessions);
        $this->expectRefreshTokenRevocations([
            $activeSession->getId(),
            $alreadyRevokedSession->getId(),
            $secondActiveSession->getId(),
        ]);
        $this->expectSavedRevokedSessions();

        return [$activeSession->getId(), $secondActiveSession->getId()];
    }

    /**
     * @param list<string> $sessionIds
     */
    private function expectRefreshTokenRevocations(array $sessionIds): void
    {
        $this->refreshTokenRepository->expects($this->exactly(3))
            ->method('revokeBySessionId')
            ->with($this->callback(
                static fn (string $sessionId): bool => in_array($sessionId, $sessionIds, true)
            ));
    }

    private function expectSavedRevokedSessions(): void
    {
        $this->sessionRepository->expects($this->exactly(2))
            ->method('save')
            ->willReturnCallback(function (AuthSession $session): void {
                self::assertTrue($session->isRevoked());
                $this->savedSessionIds[] = $session->getId();
            });
    }

    private function createSession(string $sessionId, string $userId): AuthSession
    {
        $createdAt = new DateTimeImmutable('-5 minutes');

        return new AuthSession(
            $sessionId,
            $userId,
            '127.0.0.1',
            'Test Agent',
            $createdAt,
            $createdAt->modify('+15 minutes'),
            false
        );
    }
}
