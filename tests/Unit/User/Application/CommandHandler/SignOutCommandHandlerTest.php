<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\SignOutCommand;
use App\User\Application\CommandHandler\SignOutCommandHandler;
use App\User\Application\EventPublisher\SessionEventsInterface;
use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Repository\AuthRefreshTokenRepositoryInterface;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;

final class SignOutCommandHandlerTest extends UnitTestCase
{
    private AuthSessionRepositoryInterface&MockObject $sessionRepository;
    private AuthRefreshTokenRepositoryInterface&MockObject $refreshTokenRepository;
    private SessionEventsInterface&MockObject $sessionEvents;
    private SignOutCommandHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->sessionRepository = $this->createMock(AuthSessionRepositoryInterface::class);
        $this->refreshTokenRepository =
            $this->createMock(AuthRefreshTokenRepositoryInterface::class);
        $this->sessionEvents = $this->createMock(SessionEventsInterface::class);
        $this->handler = new SignOutCommandHandler(
            $this->sessionRepository,
            $this->refreshTokenRepository,
            $this->sessionEvents
        );
    }

    public function testInvokeRevokesSessionAndTokens(): void
    {
        $sessionId = $this->faker->uuid();
        $userId = $this->faker->uuid();
        $command = new SignOutCommand($sessionId, $userId);
        $session = $this->createMock(AuthSession::class);
        $this->expectSessionRevocation($sessionId, $session);
        $this->expectRefreshTokenRevocation($sessionId);
        $this->expectSessionRevokedEvent($userId, $sessionId);
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
        $this->sessionEvents->expects($this->never())->method('publishSessionRevoked');
        $this->handler->__invoke($command);
    }

    private function expectSessionRevocation(
        string $sessionId,
        AuthSession&MockObject $session
    ): void {
        $this->sessionRepository->expects($this->once())
            ->method('findById')
            ->with($sessionId)
            ->willReturn($session);
        $session->expects($this->once())
            ->method('revoke');
        $this->sessionRepository->expects($this->once())
            ->method('save')
            ->with($session);
    }

    private function expectRefreshTokenRevocation(string $sessionId): void
    {
        $this->refreshTokenRepository->expects($this->once())
            ->method('revokeBySessionId')
            ->with($sessionId);
    }

    private function expectSessionRevokedEvent(string $userId, string $sessionId): void
    {
        $this->sessionEvents->expects($this->once())
            ->method('publishSessionRevoked')
            ->with($userId, $sessionId, 'logout');
    }
}
