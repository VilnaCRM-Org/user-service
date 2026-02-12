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
        $recoveryCodeUsed = false;

        $event = new TwoFactorCompletedEvent($userId, $sessionId, $this->faker->ipv4(), $this->faker->userAgent(), "totp", $this->faker->uuid());

        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                'Two-factor authentication completed',
                $this->callback(function ($context) use ($userId, $sessionId, $recoveryCodeUsed) {
                    return $context['event'] === 'user.two_factor.completed'
                        && $context['user_id'] === $userId
                        && $context['session_id'] === $sessionId
                        && $context['recovery_code_used'] === $recoveryCodeUsed;
                })
            );

        $this->subscriber->__invoke($event);
    }

    public function testInvokeLogsTwoFactorFailedAtWarningLevel(): void
    {
        $userId = $this->faker->uuid();
        $pendingSessionId = $this->faker->uuid();
        $reason = 'invalid_code';

        $event = new TwoFactorFailedEvent($pendingSessionId, $this->faker->ipv4(), $reason, $this->faker->uuid());

        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                'Two-factor authentication failed',
                $this->callback(function ($context) use ($userId, $pendingSessionId, $reason) {
                    return $context['event'] === 'user.two_factor.failed'
                        && $context['user_id'] === $userId
                        && $context['pending_session_id'] === $pendingSessionId
                        && $context['reason'] === $reason;
                })
            );

        $this->subscriber->__invoke($event);
    }

    public function testInvokeLogsTwoFactorEnabledAtInfoLevel(): void
    {
        $userId = $this->faker->uuid();
        $recoveryCodesCount = 8;

        $event = new TwoFactorEnabledEvent($userId, $this->faker->email(), $this->faker->uuid());

        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                'Two-factor authentication enabled',
                $this->callback(function ($context) use ($userId, $recoveryCodesCount) {
                    return $context['event'] === 'user.two_factor.enabled'
                        && $context['user_id'] === $userId
                        && $context['recovery_codes_generated'] === $recoveryCodesCount;
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
                $this->callback(function ($context) use ($userId) {
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
