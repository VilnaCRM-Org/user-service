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

        $capturedContext = [];
        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                'Recovery code used',
                $this->callback(static function (array $context) use (&$capturedContext): bool {
                    $capturedContext = $context;
                    return true;
                })
            );

        $this->subscriber->__invoke($event);

        $this->assertSame('user.recovery_code.used', $capturedContext['event']);
        $this->assertSame($userId, $capturedContext['user_id']);
        $this->assertSame($remainingCodes, $capturedContext['remaining_count']);
        $this->assertArrayHasKey('timestamp', $capturedContext);
    }

    public function testInvokeLogsAccountLockedOutAtWarningLevel(): void
    {
        $email = $this->faker->email();
        $failedAttempts = 5;
        $lockoutDuration = 900;

        $event = new AccountLockedOutEvent($email, $failedAttempts, $lockoutDuration, $this->faker->uuid());

        $capturedContext = [];
        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                'Account locked out due to failed attempts',
                $this->callback(static function (array $context) use (&$capturedContext): bool {
                    $capturedContext = $context;
                    return true;
                })
            );

        $this->subscriber->__invoke($event);

        $this->assertSame('user.account.locked_out', $capturedContext['event']);
        $this->assertSame($email, $capturedContext['email']);
        $this->assertSame($failedAttempts, $capturedContext['failed_attempts']);
        $this->assertSame($lockoutDuration, $capturedContext['lockout_duration_seconds']);
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
                RecoveryCodeUsedEvent::class,
                AccountLockedOutEvent::class,
            ],
            $this->subscriber->subscribedTo()
        );
    }
}
