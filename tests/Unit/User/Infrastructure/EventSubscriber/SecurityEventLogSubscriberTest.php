<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\EventSubscriber;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Event\AccountLockedOutEvent;
use App\User\Domain\Event\RecoveryCodeUsedEvent;
use App\User\Infrastructure\EventSubscriber\SecurityEventLogSubscriber;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

final class SecurityEventLogSubscriberTest extends UnitTestCase
{
    private LoggerInterface&MockObject $logger;
    private SecurityEventLogSubscriber $subscriber;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->subscriber = new SecurityEventLogSubscriber($this->logger);
    }

    public function testInvokeLogsRecoveryCodeUsedAtWarningLevel(): void
    {
        $userId = $this->faker->uuid();
        $remainingCodes = 5;

        $event = new RecoveryCodeUsedEvent($userId, $remainingCodes, $this->faker->uuid());

        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                'Recovery code used',
                $this->callback(function ($context) use ($userId, $remainingCodes) {
                    return $context['event'] === 'user.recovery_code.used'
                        && $context['user_id'] === $userId
                        && $context['remaining_codes'] === $remainingCodes;
                })
            );

        $this->subscriber->__invoke($event);
    }

    public function testInvokeLogsAccountLockedOutAtWarningLevel(): void
    {
        $email = $this->faker->email();
        $failedAttempts = 5;
        $lockoutDuration = 900;

        $event = new AccountLockedOutEvent($email, $failedAttempts, $lockoutDuration, $this->faker->uuid());

        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                'Account locked out due to failed attempts',
                $this->callback(function ($context) use ($email, $failedAttempts, $lockoutDuration) {
                    return $context['event'] === 'user.account.locked_out'
                        && $context['email'] === $email
                        && $context['failed_attempts'] === $failedAttempts
                        && $context['lockout_duration_seconds'] === $lockoutDuration;
                })
            );

        $this->subscriber->__invoke($event);
    }

    public function testSubscribedToReturnsCorrectEvents(): void
    {
        $this->assertSame(
            [
                RecoveryCodeUsedEvent::class,
                AccountLockedOutEvent::class,
            ],
            $this->subscriber->subscribedTo()
        );
    }
}
