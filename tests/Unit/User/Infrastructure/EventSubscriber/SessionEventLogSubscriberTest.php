<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\EventSubscriber;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Event\AllSessionsRevokedEvent;
use App\User\Domain\Event\SessionRevokedEvent;
use App\User\Infrastructure\EventSubscriber\SessionEventLogSubscriber;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

final class SessionEventLogSubscriberTest extends UnitTestCase
{
    private LoggerInterface&MockObject $logger;
    private SessionEventLogSubscriber $subscriber;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->subscriber = new SessionEventLogSubscriber($this->logger);
    }

    public function testInvokeLogsSessionRevokedAtInfoLevel(): void
    {
        $userId = $this->faker->uuid();
        $sessionId = $this->faker->uuid();
        $reason = 'logout';

        $event = new SessionRevokedEvent($userId, $sessionId, $reason, $this->faker->uuid());

        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                'Session revoked',
                $this->callback(function ($context) use ($userId, $sessionId, $reason) {
                    return $context['event'] === 'user.session.revoked'
                        && $context['user_id'] === $userId
                        && $context['session_id'] === $sessionId
                        && $context['reason'] === $reason;
                })
            );

        $this->subscriber->__invoke($event);
    }

    public function testInvokeLogsAllSessionsRevokedAtInfoLevel(): void
    {
        $userId = $this->faker->uuid();
        $reason = 'user_initiated';
        $revokedCount = 3;

        $event = new AllSessionsRevokedEvent($userId, $reason, $revokedCount, $this->faker->uuid());

        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                'All sessions revoked',
                $this->callback(function ($context) use ($userId, $reason) {
                    return $context['event'] === 'user.sessions.all_revoked'
                        && $context['user_id'] === $userId
                        && $context['reason'] === $reason;
                })
            );

        $this->subscriber->__invoke($event);
    }

    public function testSubscribedToReturnsCorrectEvents(): void
    {
        $this->assertSame(
            [
                SessionRevokedEvent::class,
                AllSessionsRevokedEvent::class,
            ],
            $this->subscriber->subscribedTo()
        );
    }
}
