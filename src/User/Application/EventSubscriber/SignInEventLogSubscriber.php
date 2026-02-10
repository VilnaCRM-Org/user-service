<?php

declare(strict_types=1);

namespace App\User\Application\EventSubscriber;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use App\User\Domain\Event\AccountLockedOutEvent;
use App\User\Domain\Event\SignInFailedEvent;
use App\User\Domain\Event\TwoFactorCompletedEvent;
use App\User\Domain\Event\TwoFactorFailedEvent;
use App\User\Domain\Event\UserSignedInEvent;
use Psr\Log\LoggerInterface;

final readonly class SignInEventLogSubscriber implements
    DomainEventSubscriberInterface
{
    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(DomainEvent $event): void
    {
        if ($event instanceof UserSignedInEvent) {
            $this->logger->info('User signed in', [
                'userId' => $event->userId,
                'email' => $event->email,
                'sessionId' => $event->sessionId,
                'ipAddress' => $event->ipAddress,
                'userAgent' => $event->userAgent,
            ]);

            return;
        }

        if ($event instanceof SignInFailedEvent) {
            $this->logger->warning('Sign-in failed', [
                'email' => $event->email,
                'ipAddress' => $event->ipAddress,
                'userAgent' => $event->userAgent,
            ]);

            return;
        }

        if ($event instanceof AccountLockedOutEvent) {
            $this->logger->warning('Account locked out', [
                'email' => $event->email,
                'ipAddress' => $event->ipAddress,
                'userAgent' => $event->userAgent,
            ]);

            return;
        }

        if ($event instanceof TwoFactorCompletedEvent) {
            $this->logger->info('Two-factor completed', [
                'userId' => $event->userId,
                'sessionId' => $event->sessionId,
                'ipAddress' => $event->ipAddress,
                'userAgent' => $event->userAgent,
                'method' => $event->method,
            ]);

            return;
        }

        if ($event instanceof TwoFactorFailedEvent) {
            $this->logger->warning('Two-factor failed', [
                'pendingSessionId' => $event->pendingSessionId,
                'ipAddress' => $event->ipAddress,
                'reason' => $event->reason,
            ]);
        }
    }

    /**
     * @return array<string>
     *
     * @psalm-return list{
     *   UserSignedInEvent::class,
     *   SignInFailedEvent::class,
     *   AccountLockedOutEvent::class,
     *   TwoFactorCompletedEvent::class,
     *   TwoFactorFailedEvent::class
     * }
     */
    #[\Override]
    public function subscribedTo(): array
    {
        return [
            UserSignedInEvent::class,
            SignInFailedEvent::class,
            AccountLockedOutEvent::class,
            TwoFactorCompletedEvent::class,
            TwoFactorFailedEvent::class,
        ];
    }
}
