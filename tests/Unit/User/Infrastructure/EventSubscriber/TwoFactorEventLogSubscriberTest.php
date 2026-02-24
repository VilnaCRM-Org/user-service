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
        $ip = $this->faker->ipv4();
        $ua = $this->faker->userAgent();
        $eventId = $this->faker->uuid();
        $event = new TwoFactorCompletedEvent($userId, $sessionId, $ip, $ua, 'totp', $eventId);
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
        $this->assertCompletedContext($capturedContext, $userId, $sessionId);
    }

    public function testInvokeLogsTwoFactorFailedAtWarningLevel(): void
    {
        $pendingSessionId = $this->faker->uuid();
        $ipAddress = $this->faker->ipv4();
        $event = new TwoFactorFailedEvent(
            $pendingSessionId,
            $ipAddress,
            'invalid_code',
            $this->faker->uuid()
        );
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
        $this->assertFailedContext($capturedContext, $pendingSessionId, $ipAddress);
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

    /**
     * @param array<string, string> $context
     */
    private function assertCompletedContext(
        array $context,
        string $userId,
        string $sessionId
    ): void {
        $this->assertSame('user.two_factor.completed', $context['event']);
        $this->assertSame($userId, $context['user_id']);
        $this->assertSame($sessionId, $context['session_id']);
        $this->assertSame('totp', $context['method']);
        $this->assertArrayHasKey('timestamp', $context);
    }

    /**
     * @param array<string, string> $context
     */
    private function assertFailedContext(
        array $context,
        string $pendingSessionId,
        string $ipAddress
    ): void {
        $this->assertSame('user.two_factor.failed', $context['event']);
        $this->assertSame($pendingSessionId, $context['pending_session_id']);
        $this->assertSame($ipAddress, $context['ip_address']);
        $this->assertSame('invalid_code', $context['reason']);
        $this->assertArrayHasKey('timestamp', $context);
    }
}
