<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\EventSubscriber;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Event\TwoFactorCompletedEvent;
use App\User\Domain\Event\TwoFactorDisabledEvent;
use App\User\Domain\Event\TwoFactorEnabledEvent;
use App\User\Domain\Event\TwoFactorFailedEvent;
use App\User\Infrastructure\EventSubscriber\TwoFactorEventLogSubscriber;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

final class TwoFactorEventLogSubscriberTest extends UnitTestCase
{
    private LoggerInterface&MockObject $logger;
    private TwoFactorEventLogSubscriber $subscriber;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->subscriber = new TwoFactorEventLogSubscriber($this->logger);
    }

    public function testInvokeLogsTwoFactorCompletedAtInfoLevel(): void
    {
        $userId = $this->faker->uuid();
        $sessionId = $this->faker->uuid();
        $method = 'totp';

        $event = new TwoFactorCompletedEvent($userId, $sessionId, $this->faker->ipv4(), $this->faker->userAgent(), $method, $this->faker->uuid());

        $capturedContext = [];
        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                'Two-factor authentication completed',
                $this->callback(static function (array $context) use (&$capturedContext): bool {
                    $capturedContext = $context;
                    return true;
                })
            );

        $this->subscriber->__invoke($event);

        $this->assertSame('user.two_factor.completed', $capturedContext['event']);
        $this->assertSame($userId, $capturedContext['user_id']);
        $this->assertSame($sessionId, $capturedContext['session_id']);
        $this->assertSame($method, $capturedContext['method']);
        $this->assertArrayHasKey('timestamp', $capturedContext);
    }

    public function testInvokeLogsTwoFactorFailedAtWarningLevel(): void
    {
        $pendingSessionId = $this->faker->uuid();
        $ipAddress = $this->faker->ipv4();
        $reason = 'invalid_code';

        $event = new TwoFactorFailedEvent($pendingSessionId, $ipAddress, $reason, $this->faker->uuid());

        $capturedContext = [];
        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                'Two-factor authentication failed',
                $this->callback(static function (array $context) use (&$capturedContext): bool {
                    $capturedContext = $context;
                    return true;
                })
            );

        $this->subscriber->__invoke($event);

        $this->assertSame('user.two_factor.failed', $capturedContext['event']);
        $this->assertSame($pendingSessionId, $capturedContext['pending_session_id']);
        $this->assertSame($ipAddress, $capturedContext['ip_address']);
        $this->assertSame($reason, $capturedContext['reason']);
        $this->assertArrayHasKey('timestamp', $capturedContext);
    }

    public function testInvokeLogsTwoFactorEnabledAtInfoLevel(): void
    {
        $userId = $this->faker->uuid();
        $email = $this->faker->email();

        $event = new TwoFactorEnabledEvent($userId, $email, $this->faker->uuid());

        $capturedContext = [];
        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                'Two-factor authentication enabled',
                $this->callback(static function (array $context) use (&$capturedContext): bool {
                    $capturedContext = $context;
                    return true;
                })
            );

        $this->subscriber->__invoke($event);

        $this->assertSame('user.two_factor.enabled', $capturedContext['event']);
        $this->assertSame($userId, $capturedContext['user_id']);
        $this->assertSame($email, $capturedContext['email']);
        $this->assertArrayHasKey('timestamp', $capturedContext);
    }

    public function testInvokeLogsTwoFactorDisabledAtInfoLevel(): void
    {
        $userId = $this->faker->uuid();

        $event = new TwoFactorDisabledEvent($userId, $this->faker->email(), $this->faker->uuid());

        $capturedContext = [];
        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                'Two-factor authentication disabled',
                $this->callback(static function (array $context) use (&$capturedContext): bool {
                    $capturedContext = $context;
                    return true;
                })
            );

        $this->subscriber->__invoke($event);

        $this->assertSame('user.two_factor.disabled', $capturedContext['event']);
        $this->assertSame($userId, $capturedContext['user_id']);
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
                TwoFactorCompletedEvent::class,
                TwoFactorFailedEvent::class,
                TwoFactorEnabledEvent::class,
                TwoFactorDisabledEvent::class,
            ],
            $this->subscriber->subscribedTo()
        );
    }
}
