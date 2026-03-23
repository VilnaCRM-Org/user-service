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

        $capturedContext = [];
        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                'Session revoked',
                $this->callback(static function (array $context) use (&$capturedContext): bool {
                    $capturedContext = $context;
                    return true;
                })
            );

        $this->subscriber->__invoke($event);

        $this->assertSame('user.session.revoked', $capturedContext['event']);
        $this->assertSame($userId, $capturedContext['userId']);
        $this->assertSame($sessionId, $capturedContext['sessionId']);
        $this->assertSame($reason, $capturedContext['reason']);
        $this->assertArrayHasKey('timestamp', $capturedContext);
    }

    public function testInvokeLogsAllSessionsRevokedAtInfoLevel(): void
    {
        $userId = $this->faker->uuid();
        $reason = 'user_initiated';
        $revokedCount = 3;

        $event = new AllSessionsRevokedEvent($userId, $reason, $revokedCount, $this->faker->uuid());

        $capturedContext = [];
        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                'All sessions revoked',
                $this->callback(static function (array $context) use (&$capturedContext): bool {
                    $capturedContext = $context;
                    return true;
                })
            );

        $this->subscriber->__invoke($event);

        $this->assertSame('user.sessions.all_revoked', $capturedContext['event']);
        $this->assertSame($userId, $capturedContext['userId']);
        $this->assertSame($reason, $capturedContext['reason']);
        $this->assertArrayHasKey('timestamp', $capturedContext);
    }

    public function testInvokeIgnoresUnknownEvent(): void
    {
        $this->logger->expects($this->never())->method('info');
        $this->logger->expects($this->never())->method('warning');
        $this->logger->expects($this->never())->method('critical');

        $this->subscriber->__invoke(new \stdClass());
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
