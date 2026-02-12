<?php

declare(strict_types=1);

namespace App\User\Infrastructure\EventSubscriber;

use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use App\User\Domain\Event\AccountLockedOutEvent;
use App\User\Domain\Event\RecoveryCodeUsedEvent;
use Psr\Log\LoggerInterface;

/**
 * Audit logging for security events (recovery codes, account lockout).
 *
 * AC: NFR-33 - Structured logging for security investigation
 */
final readonly class SecurityEventLogSubscriber implements DomainEventSubscriberInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(object $event): void
    {
        match (true) {
            $event instanceof RecoveryCodeUsedEvent => $this->logRecoveryCodeUsed($event),
            $event instanceof AccountLockedOutEvent => $this->logAccountLockedOut($event),
            default => null, // @codeCoverageIgnore
        };
    }

    /**
     * @return array<string>
     *
     * @psalm-return list{RecoveryCodeUsedEvent::class, AccountLockedOutEvent::class}
     */
    #[\Override]
    public function subscribedTo(): array
    {
        return [
            RecoveryCodeUsedEvent::class,
            AccountLockedOutEvent::class,
        ];
    }

    private function logRecoveryCodeUsed(RecoveryCodeUsedEvent $event): void
    {
        // AC: NFR-33 #4 - Recovery code use at WARNING level
        $this->logger->warning('Recovery code used', [
            'event' => 'user.recovery_code.used',
            'user_id' => $event->userId,
            'remaining_codes' => $event->remainingCodes,
            'timestamp' => $event->occurredOn()->format(\DateTimeInterface::ATOM),
        ]);
    }

    private function logAccountLockedOut(AccountLockedOutEvent $event): void
    {
        $this->logger->warning('Account locked out due to failed attempts', [
            'event' => 'user.account.locked_out',
            'email' => $event->email,
            'failed_attempts' => $event->failedAttempts,
            'lockout_duration_seconds' => $event->lockoutDurationSeconds,
            'timestamp' => $event->occurredOn()->format(\DateTimeInterface::ATOM),
        ]);
    }
}
