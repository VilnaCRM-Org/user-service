<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\SignOutAllCommand;
use App\User\Application\CommandHandler\SignOutAllCommandHandler;
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

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->sessionRepository = $this->createMock(AuthSessionRepositoryInterface::class);
        $this->refreshTokenRepository = $this->createMock(AuthRefreshTokenRepositoryInterface::class);
        $this->sessionEvents = $this->createMock(SessionPublisherInterface::class);
        $this->handler = new SignOutAllCommandHandler(
            $this->sessionRepository,
            $this->refreshTokenRepository,
            $this->sessionEvents
        );
    }

    public function testInvokeRevokesSessionsAndPublishesAllSessionsRevokedEvent(): void
    {
        $userId = $this->faker->uuid();
        $activeSession = $this->createSession($this->faker->uuid(), $userId);
        $alreadyRevokedSession = $this->createSession($this->faker->uuid(), $userId);
        $alreadyRevokedSession->revoke();

        $this->sessionRepository->expects($this->once())
            ->method('findByUserId')
            ->with($userId)
            ->willReturn([$activeSession, $alreadyRevokedSession]);

        $this->refreshTokenRepository->expects($this->exactly(2))
            ->method('revokeBySessionId')
            ->with(
                $this->logicalOr(
                    $this->equalTo($activeSession->getId()),
                    $this->equalTo($alreadyRevokedSession->getId())
                )
            );

        $this->sessionRepository->expects($this->once())
            ->method('save')
            ->with($activeSession);

        $this->sessionEvents->expects($this->once())
            ->method('publishAllSessionsRevoked')
            ->with($userId, 'user_initiated', 1);

        $this->handler->__invoke(new SignOutAllCommand($userId));
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
