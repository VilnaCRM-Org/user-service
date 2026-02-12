<?php

declare(strict_types=1);

namespace App\User\Infrastructure\EventSubscriber;

use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use App\User\Domain\Event\AllSessionsRevokedEvent;
use App\User\Domain\Event\SessionRevokedEvent;
use Psr\Log\LoggerInterface;

/**
 * Audit logging for session management events.
 *
 * AC: NFR-33 - Structured logging for security investigation
 */
final readonly class SessionEventLogSubscriber implements DomainEventSubscriberInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(object $event): void
    {
        match (true) {
            $event instanceof SessionRevokedEvent => $this->logSessionRevoked($event),
            $event instanceof AllSessionsRevokedEvent => $this->logAllSessionsRevoked($event),
            default => null, // @codeCoverageIgnore
        };
    }

    /**
     * @return array<string>
     *
     * @psalm-return list{SessionRevokedEvent::class, AllSessionsRevokedEvent::class}
     */
    #[\Override]
    public function subscribedTo(): array
    {
        return [
            SessionRevokedEvent::class,
            AllSessionsRevokedEvent::class,
        ];
    }

    private function logSessionRevoked(SessionRevokedEvent $event): void
    {
        // AC: NFR-33 #6 - Session revoked at INFO with reason
        $this->logger->info('Session revoked', [
            'event' => 'user.session.revoked',
            'user_id' => $event->userId,
            'session_id' => $event->sessionId,
            'reason' => $event->reason,
            'timestamp' => $event->occurredOn()->format(\DateTimeInterface::ATOM),
        ]);
    }

    private function logAllSessionsRevoked(AllSessionsRevokedEvent $event): void
    {
        // AC: NFR-33 #6 - All sessions revoked at INFO with reason
        $this->logger->info('All sessions revoked', [
            'event' => 'user.sessions.all_revoked',
            'user_id' => $event->userId,
            'reason' => $event->reason,
            'timestamp' => $event->occurredOn()->format(\DateTimeInterface::ATOM),
        ]);
    }
}
