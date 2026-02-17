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

        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                'Two-factor authentication completed',
                $this->callback(static function ($context) use ($userId, $sessionId, $method) {
                    return $context['event'] === 'user.two_factor.completed'
                        && $context['user_id'] === $userId
                        && $context['session_id'] === $sessionId
                        && $context['method'] === $method;
                })
            );

        $this->subscriber->__invoke($event);
    }

    public function testInvokeLogsTwoFactorFailedAtWarningLevel(): void
    {
        $pendingSessionId = $this->faker->uuid();
        $ipAddress = $this->faker->ipv4();
        $reason = 'invalid_code';

        $event = new TwoFactorFailedEvent($pendingSessionId, $ipAddress, $reason, $this->faker->uuid());

        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                'Two-factor authentication failed',
                $this->callback(static function ($context) use ($pendingSessionId, $ipAddress, $reason) {
                    return $context['event'] === 'user.two_factor.failed'
                        && $context['pending_session_id'] === $pendingSessionId
                        && $context['ip_address'] === $ipAddress
                        && $context['reason'] === $reason;
                })
            );

        $this->subscriber->__invoke($event);
    }

    public function testInvokeLogsTwoFactorEnabledAtInfoLevel(): void
    {
        $userId = $this->faker->uuid();
        $email = $this->faker->email();

        $event = new TwoFactorEnabledEvent($userId, $email, $this->faker->uuid());

        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                'Two-factor authentication enabled',
                $this->callback(static function ($context) use ($userId, $email) {
                    return $context['event'] === 'user.two_factor.enabled'
                        && $context['user_id'] === $userId
                        && $context['email'] === $email;
                })
            );

        $this->subscriber->__invoke($event);
    }

    public function testInvokeLogsTwoFactorDisabledAtInfoLevel(): void
    {
        $userId = $this->faker->uuid();

        $event = new TwoFactorDisabledEvent($userId, $this->faker->email(), $this->faker->uuid());

        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                'Two-factor authentication disabled',
                $this->callback(static function ($context) use ($userId) {
                    return $context['event'] === 'user.two_factor.disabled'
                        && $context['user_id'] === $userId;
                })
            );

        $this->subscriber->__invoke($event);
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
