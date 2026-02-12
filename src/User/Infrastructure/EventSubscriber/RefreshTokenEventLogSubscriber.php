<?php

declare(strict_types=1);

namespace App\User\Infrastructure\EventSubscriber;

use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use App\User\Domain\Event\RefreshTokenRotatedEvent;
use App\User\Domain\Event\RefreshTokenTheftDetectedEvent;
use Psr\Log\LoggerInterface;

/**
 * Audit logging for refresh token events.
 *
 * AC: NFR-33, NFR-34 - Structured logging for security investigation
 */
final readonly class RefreshTokenEventLogSubscriber implements DomainEventSubscriberInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(object $event): void
    {
        match (true) {
            $event instanceof RefreshTokenRotatedEvent => $this->logRefreshTokenRotated($event),
            $event instanceof RefreshTokenTheftDetectedEvent => $this->logRefreshTokenTheft($event),
            default => null, // @codeCoverageIgnore
        };
    }

    /**
     * @return array<string>
     *
     * @psalm-return list{RefreshTokenRotatedEvent::class, RefreshTokenTheftDetectedEvent::class}
     */
    #[\Override]
    public function subscribedTo(): array
    {
        return [
            RefreshTokenRotatedEvent::class,
            RefreshTokenTheftDetectedEvent::class,
        ];
    }

    private function logRefreshTokenRotated(RefreshTokenRotatedEvent $event): void
    {
        $this->logger->info('Refresh token rotated', [
            'event' => 'user.refresh_token.rotated',
            'session_id' => $event->sessionId,
            'old_token_revoked' => true,
            'timestamp' => $event->occurredOn()->format(\DateTimeInterface::ATOM),
        ]);
    }

    private function logRefreshTokenTheft(RefreshTokenTheftDetectedEvent $event): void
    {
        // AC: NFR-34 - Theft detection at CRITICAL level
        $this->logger->critical('Refresh token theft detected', [
            'event' => 'user.refresh_token.theft_detected',
            'session_id' => $event->sessionId,
            'user_id' => $event->userId,
            'ip_address' => $event->ipAddress,
            'timestamp' => $event->occurredOn()->format(\DateTimeInterface::ATOM),
        ]);
    }
}
