<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\SignOutAllCommand;
use App\User\Application\CommandHandler\SignOutAllCommandHandler;
use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Event\AllSessionsRevokedEvent;
use App\User\Domain\Repository\AuthRefreshTokenRepositoryInterface;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\EventDispatcher\EventDispatcherInterface;

final class SignOutAllCommandHandlerTest extends UnitTestCase
{
    private AuthSessionRepositoryInterface&MockObject $sessionRepository;
    private AuthRefreshTokenRepositoryInterface&MockObject $refreshTokenRepository;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private SignOutAllCommandHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->sessionRepository = $this->createMock(AuthSessionRepositoryInterface::class);
        $this->refreshTokenRepository = $this->createMock(AuthRefreshTokenRepositoryInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->handler = new SignOutAllCommandHandler(
            $this->sessionRepository,
            $this->refreshTokenRepository,
            $this->eventDispatcher
        );
    }

    public function testInvokeRevokesAllSessionsAndTokens(): void
    {
        $userId = $this->faker->uuid();
        $command = new SignOutAllCommand($userId);

        $session1 = $this->createMock(AuthSession::class);
        $session2 = $this->createMock(AuthSession::class);
        $sessions = [$session1, $session2];

        $this->sessionRepository->expects($this->once())
            ->method('findByUserId')
            ->with($userId)
            ->willReturn($sessions);

        $session1->expects($this->once())
            ->method('isRevoked')
            ->willReturn(false);
        $session1->expects($this->once())
            ->method('revoke');
        $session1->expects($this->once())
            ->method('getId')
            ->willReturn($this->faker->uuid());

        $session2->expects($this->once())
            ->method('isRevoked')
            ->willReturn(false);
        $session2->expects($this->once())
            ->method('revoke');
        $session2->expects($this->once())
            ->method('getId')
            ->willReturn($this->faker->uuid());

        $this->sessionRepository->expects($this->exactly(2))
            ->method('save');

        $this->refreshTokenRepository->expects($this->exactly(2))
            ->method('revokeBySessionId');

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (AllSessionsRevokedEvent $event) use ($userId) {
                return $event->userId === $userId
                    && $event->reason === 'user_initiated'
                    && $event->revokedCount === 2;
            }));

        $this->handler->__invoke($command);
    }

    public function testInvokeSkipsAlreadyRevokedSessions(): void
    {
        $userId = $this->faker->uuid();
        $command = new SignOutAllCommand($userId);

        $activeSession = $this->createMock(AuthSession::class);
        $revokedSession = $this->createMock(AuthSession::class);
        $sessions = [$activeSession, $revokedSession];

        $this->sessionRepository->expects($this->once())
            ->method('findByUserId')
            ->with($userId)
            ->willReturn($sessions);

        $activeSession->expects($this->once())
            ->method('isRevoked')
            ->willReturn(false);
        $activeSession->expects($this->once())
            ->method('revoke');
        $activeSession->expects($this->once())
            ->method('getId')
            ->willReturn($this->faker->uuid());

        $revokedSession->expects($this->once())
            ->method('isRevoked')
            ->willReturn(true);
        $revokedSession->expects($this->never())
            ->method('revoke');

        $this->sessionRepository->expects($this->once())
            ->method('save')
            ->with($activeSession);

        $this->refreshTokenRepository->expects($this->once())
            ->method('revokeBySessionId');

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (AllSessionsRevokedEvent $event) use ($userId) {
                return $event->userId === $userId
                    && $event->revokedCount === 1;
            }));

        $this->handler->__invoke($command);
    }
}
