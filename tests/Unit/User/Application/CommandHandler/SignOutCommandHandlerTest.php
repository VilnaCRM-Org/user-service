<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\SignOutCommand;
use App\User\Application\CommandHandler\SignOutCommandHandler;
use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Event\SessionRevokedEvent;
use App\User\Domain\Repository\AuthRefreshTokenRepositoryInterface;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\EventDispatcher\EventDispatcherInterface;

final class SignOutCommandHandlerTest extends UnitTestCase
{
    private AuthSessionRepositoryInterface&MockObject $sessionRepository;
    private AuthRefreshTokenRepositoryInterface&MockObject $refreshTokenRepository;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private SignOutCommandHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->sessionRepository = $this->createMock(AuthSessionRepositoryInterface::class);
        $this->refreshTokenRepository = $this->createMock(AuthRefreshTokenRepositoryInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->handler = new SignOutCommandHandler(
            $this->sessionRepository,
            $this->refreshTokenRepository,
            $this->eventDispatcher
        );
    }

    public function testInvokeRevokesSessionAndTokens(): void
    {
        $sessionId = $this->faker->uuid();
        $userId = $this->faker->uuid();
        $command = new SignOutCommand($sessionId, $userId);

        $session = $this->createMock(AuthSession::class);

        $this->sessionRepository->expects($this->once())
            ->method('findById')
            ->with($sessionId)
            ->willReturn($session);

        $session->expects($this->once())
            ->method('revoke');

        $this->sessionRepository->expects($this->once())
            ->method('save')
            ->with($session);

        $this->refreshTokenRepository->expects($this->once())
            ->method('revokeBySessionId')
            ->with($sessionId);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (SessionRevokedEvent $event) use ($userId, $sessionId) {
                return $event->userId === $userId
                    && $event->sessionId === $sessionId
                    && $event->reason === 'logout';
            }));

        $this->handler->__invoke($command);
    }

    public function testInvokeHandlesNullSession(): void
    {
        $sessionId = $this->faker->uuid();
        $userId = $this->faker->uuid();
        $command = new SignOutCommand($sessionId, $userId);

        $this->sessionRepository->expects($this->once())
            ->method('findById')
            ->with($sessionId)
            ->willReturn(null);

        $this->sessionRepository->expects($this->never())
            ->method('save');

        $this->refreshTokenRepository->expects($this->once())
            ->method('revokeBySessionId')
            ->with($sessionId);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(SessionRevokedEvent::class));

        $this->handler->__invoke($command);
    }
}
