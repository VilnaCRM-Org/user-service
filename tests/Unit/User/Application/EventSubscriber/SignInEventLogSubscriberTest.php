<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\EventSubscriber;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\EventSubscriber\SignInEventLogSubscriber;
use App\User\Domain\Event\AccountLockedOutEvent;
use App\User\Domain\Event\AllSessionsRevokedEvent;
use App\User\Domain\Event\RefreshTokenRotatedEvent;
use App\User\Domain\Event\RefreshTokenTheftDetectedEvent;
use App\User\Domain\Event\SignInFailedEvent;
use App\User\Domain\Event\TwoFactorCompletedEvent;
use App\User\Domain\Event\TwoFactorFailedEvent;
use App\User\Domain\Event\UserSignedInEvent;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

final class SignInEventLogSubscriberTest extends UnitTestCase
{
    private LoggerInterface&MockObject $logger;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testInvokeLogsUserSignedInAtInfoLevel(): void
    {
        $event = new UserSignedInEvent(
            $this->faker->uuid(),
            $this->faker->email(),
            $this->faker->uuid(),
            $this->faker->ipv4(),
            $this->faker->userAgent(),
            false,
            $this->faker->uuid(),
        );

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with('User signed in', [
                'userId' => $event->userId,
                'email' => $event->email,
                'sessionId' => $event->sessionId,
                'ipAddress' => $event->ipAddress,
                'userAgent' => $event->userAgent,
            ]);

        $this->logger
            ->expects($this->never())
            ->method('warning');

        $subscriber = new SignInEventLogSubscriber($this->logger);
        $subscriber->__invoke($event);
    }

    public function testInvokeLogsSignInFailedAtWarningLevel(): void
    {
        $event = new SignInFailedEvent(
            $this->faker->email(),
            $this->faker->ipv4(),
            $this->faker->userAgent(),
            'invalid_credentials',
            $this->faker->uuid(),
        );

        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with('Sign-in failed', [
                'email' => $event->email,
                'ipAddress' => $event->ipAddress,
                'userAgent' => $event->userAgent,
            ]);

        $this->logger
            ->expects($this->never())
            ->method('info');

        $subscriber = new SignInEventLogSubscriber($this->logger);
        $subscriber->__invoke($event);
    }

    public function testInvokeLogsAccountLockedOutAtWarningLevel(): void
    {
        $event = new AccountLockedOutEvent(
            $this->faker->email(),
            $this->faker->ipv4(),
            $this->faker->userAgent(),
            $this->faker->uuid(),
        );

        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with('Account locked out', [
                'email' => $event->email,
                'ipAddress' => $event->ipAddress,
                'userAgent' => $event->userAgent,
            ]);

        $this->logger
            ->expects($this->never())
            ->method('info');

        $subscriber = new SignInEventLogSubscriber($this->logger);
        $subscriber->__invoke($event);
    }

    public function testSubscribedToReturnsSignInDomainEvents(): void
    {
        $subscriber = new SignInEventLogSubscriber($this->logger);

        $this->assertSame(
            [
                UserSignedInEvent::class,
                SignInFailedEvent::class,
                AccountLockedOutEvent::class,
                TwoFactorCompletedEvent::class,
                TwoFactorFailedEvent::class,
                RefreshTokenRotatedEvent::class,
                RefreshTokenTheftDetectedEvent::class,
                AllSessionsRevokedEvent::class,
            ],
            $subscriber->subscribedTo()
        );
    }

    public function testInvokeLogsTwoFactorCompletedAtInfoLevel(): void
    {
        $event = new TwoFactorCompletedEvent(
            $this->faker->uuid(),
            $this->faker->uuid(),
            $this->faker->ipv4(),
            $this->faker->userAgent(),
            'totp',
            $this->faker->uuid(),
        );

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with('Two-factor completed', [
                'userId' => $event->userId,
                'sessionId' => $event->sessionId,
                'ipAddress' => $event->ipAddress,
                'userAgent' => $event->userAgent,
                'method' => $event->method,
            ]);

        $this->logger
            ->expects($this->never())
            ->method('warning');

        $subscriber = new SignInEventLogSubscriber($this->logger);
        $subscriber->__invoke($event);
    }

    public function testInvokeLogsTwoFactorFailedAtWarningLevel(): void
    {
        $event = new TwoFactorFailedEvent(
            $this->faker->uuid(),
            $this->faker->ipv4(),
            'invalid_code',
            $this->faker->uuid(),
        );

        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with('Two-factor failed', [
                'pendingSessionId' => $event->pendingSessionId,
                'ipAddress' => $event->ipAddress,
                'reason' => $event->reason,
            ]);

        $this->logger
            ->expects($this->never())
            ->method('info');

        $subscriber = new SignInEventLogSubscriber($this->logger);
        $subscriber->__invoke($event);
    }

    public function testInvokeLogsRefreshTokenTheftAtCriticalLevel(): void
    {
        $event = new RefreshTokenTheftDetectedEvent(
            $this->faker->uuid(),
            $this->faker->uuid(),
            $this->faker->ipv4(),
            'double_grace_use',
            $this->faker->uuid(),
        );

        $this->logger
            ->expects($this->once())
            ->method('critical')
            ->with('Refresh token theft detected', [
                'sessionId' => $event->sessionId,
                'userId' => $event->userId,
                'ipAddress' => $event->ipAddress,
                'reason' => $event->reason,
            ]);

        $subscriber = new SignInEventLogSubscriber($this->logger);
        $subscriber->__invoke($event);
    }

    public function testInvokeLogsRefreshTokenRotatedAtDebugLevel(): void
    {
        $event = new RefreshTokenRotatedEvent(
            $this->faker->uuid(),
            $this->faker->uuid(),
            $this->faker->uuid(),
        );

        $this->logger
            ->expects($this->once())
            ->method('debug')
            ->with('Refresh token rotated', [
                'sessionId' => $event->sessionId,
                'userId' => $event->userId,
            ]);

        $subscriber = new SignInEventLogSubscriber($this->logger);
        $subscriber->__invoke($event);
    }

    public function testInvokeLogsAllSessionsRevokedAtNoticeLevel(): void
    {
        $event = new AllSessionsRevokedEvent(
            $this->faker->uuid(),
            'password_change',
            2,
            $this->faker->uuid(),
        );

        $this->logger
            ->expects($this->once())
            ->method('notice')
            ->with('All sessions revoked', [
                'userId' => $event->userId,
                'reason' => $event->reason,
                'revokedCount' => $event->revokedCount,
            ]);

        $subscriber = new SignInEventLogSubscriber($this->logger);
        $subscriber->__invoke($event);
    }

    public function testInvokeIgnoresUnknownDomainEventWithoutLogging(): void
    {
        $event = new class($this->faker->uuid(), null) extends DomainEvent {
            /** @param array<string, string|object> $body */
            #[\Override]
            public static function fromPrimitives(
                array $body,
                string $eventId,
                string $occurredOn
            ): self {
                return new self($eventId, $occurredOn);
            }

            /**
             * @return string
             *
             * @psalm-return 'unknown.event'
             */
            #[\Override]
            public static function eventName(): string
            {
                return 'unknown.event';
            }

            /**
             * @return array
             *
             * @psalm-return array<never, never>
             */
            #[\Override]
            public function toPrimitives(): array
            {
                return [];
            }
        };

        $this->logger
            ->expects($this->never())
            ->method('info');

        $this->logger
            ->expects($this->never())
            ->method('warning');

        $this->logger
            ->expects($this->never())
            ->method('critical');

        $this->logger
            ->expects($this->never())
            ->method('debug');

        $this->logger
            ->expects($this->never())
            ->method('notice');

        $subscriber = new SignInEventLogSubscriber($this->logger);
        $subscriber->__invoke($event);

        $this->addToAssertionCount(1);
    }
}
