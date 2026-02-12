<?php

declare(strict_types=1);

namespace App\User\Infrastructure\EventSubscriber;

use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use App\User\Domain\Event\SignInFailedEvent;
use App\User\Domain\Event\UserSignedInEvent;
use Psr\Log\LoggerInterface;

/**
 * Audit logging for sign-in events.
 *
 * AC: NFR-33 - Structured logging for security investigation
 */
final readonly class SignInEventLogSubscriber implements DomainEventSubscriberInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(object $event): void
    {
        match (true) {
            $event instanceof UserSignedInEvent => $this->logUserSignedIn($event),
            $event instanceof SignInFailedEvent => $this->logSignInFailed($event),
            default => null, // @codeCoverageIgnore
        };
    }

    /**
     * @return array<string>
     *
     * @psalm-return list{UserSignedInEvent::class, SignInFailedEvent::class}
     */
    #[\Override]
    public function subscribedTo(): array
    {
        return [
            UserSignedInEvent::class,
            SignInFailedEvent::class,
        ];
    }

    private function logUserSignedIn(UserSignedInEvent $event): void
    {
        // AC: NFR-33 #1 - Sign-in events at INFO level
        $this->logger->info('User signed in successfully', [
            'event' => 'user.signed_in',
            'user_id' => $event->userId,
            'session_id' => $event->sessionId,
            'ip_address' => $event->ipAddress,
            'user_agent' => $event->userAgent,
            'two_factor_used' => $event->twoFactorUsed,
            'timestamp' => $event->occurredOn()->format(\DateTimeInterface::ATOM),
        ]);
    }

    private function logSignInFailed(SignInFailedEvent $event): void
    {
        // AC: NFR-33 #2 - Failed sign-in at WARNING level
        $this->logger->warning('Sign-in attempt failed', [
            'event' => 'user.signin.failed',
            'attempted_email' => $event->email,
            'ip_address' => $event->ipAddress,
            'reason' => $event->reason,
            'timestamp' => $event->occurredOn()->format(\DateTimeInterface::ATOM),
        ]);
    }
}
