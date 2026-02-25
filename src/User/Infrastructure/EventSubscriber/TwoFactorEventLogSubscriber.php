<?php

declare(strict_types=1);

namespace App\User\Infrastructure\EventSubscriber;

use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use App\User\Domain\Event\TwoFactorCompletedEvent;
use App\User\Domain\Event\TwoFactorDisabledEvent;
use App\User\Domain\Event\TwoFactorEnabledEvent;
use App\User\Domain\Event\TwoFactorFailedEvent;
use Psr\Log\LoggerInterface;

/**
 * Audit logging for two-factor authentication events.
 *
 * AC: NFR-33 - Structured logging for security investigation
 */
final readonly class TwoFactorEventLogSubscriber implements DomainEventSubscriberInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(object $event): void
    {
        if ($event instanceof TwoFactorCompletedEvent) {
            $this->logTwoFactorCompleted($event);
        } elseif ($event instanceof TwoFactorFailedEvent) {
            $this->logTwoFactorFailed($event);
        } elseif ($event instanceof TwoFactorEnabledEvent) {
            $this->logTwoFactorEnabled($event);
        } elseif ($event instanceof TwoFactorDisabledEvent) {
            $this->logTwoFactorDisabled($event);
        }
    }

    /**
     * @return array<string>
     *
     * @psalm-return list{TwoFactorCompletedEvent::class, TwoFactorFailedEvent::class, TwoFactorEnabledEvent::class, TwoFactorDisabledEvent::class}
     */
    #[\Override]
    public function subscribedTo(): array
    {
        return [
            TwoFactorCompletedEvent::class,
            TwoFactorFailedEvent::class,
            TwoFactorEnabledEvent::class,
            TwoFactorDisabledEvent::class,
        ];
    }

    private function logTwoFactorCompleted(TwoFactorCompletedEvent $event): void
    {
        $this->logger->info('Two-factor authentication completed', [
            'event' => 'user.two_factor.completed',
            'userId' => $event->userId,
            'sessionId' => $event->sessionId,
            'method' => $event->method,
            'timestamp' => $event->occurredOn(),
        ]);
    }

    private function logTwoFactorFailed(TwoFactorFailedEvent $event): void
    {
        $this->logger->warning('Two-factor authentication failed', [
            'event' => 'user.two_factor.failed',
            'pendingSessionId' => $event->pendingSessionId,
            'ip' => $event->ipAddress,
            'reason' => $event->reason,
            'timestamp' => $event->occurredOn(),
        ]);
    }

    private function logTwoFactorEnabled(TwoFactorEnabledEvent $event): void
    {
        // AC: NFR-33 #5 - 2FA enable/disable at INFO
        $this->logger->info('Two-factor authentication enabled', [
            'event' => 'user.two_factor.enabled',
            'userId' => $event->userId,
            'email' => $event->email,
            'timestamp' => $event->occurredOn(),
        ]);
    }

    private function logTwoFactorDisabled(TwoFactorDisabledEvent $event): void
    {
        // AC: NFR-33 #5 - 2FA enable/disable at INFO
        $this->logger->info('Two-factor authentication disabled', [
            'event' => 'user.two_factor.disabled',
            'userId' => $event->userId,
            'timestamp' => $event->occurredOn(),
        ]);
    }
}
