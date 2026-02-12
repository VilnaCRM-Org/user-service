<?php

declare(strict_types=1);

namespace App\User\Application\EventSubscriber;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use App\User\Domain\Event\AccountLockedOutEvent;
use App\User\Domain\Event\AllSessionsRevokedEvent;
use App\User\Domain\Event\RefreshTokenRotatedEvent;
use App\User\Domain\Event\RefreshTokenTheftDetectedEvent;
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
        match (true) {
            $event instanceof UserSignedInEvent => $this->logSignedIn($event),
            $event instanceof SignInFailedEvent => $this->logFailed($event),
            $event instanceof AccountLockedOutEvent => $this->logLocked($event),
            $event instanceof TwoFactorCompletedEvent => $this->logTfaOk($event),
            $event instanceof TwoFactorFailedEvent => $this->logTfaFail($event),
            $event instanceof RefreshTokenRotatedEvent => $this->logRefreshTokenRotated($event),
            $event instanceof RefreshTokenTheftDetectedEvent => $this->logRefreshTokenTheft($event),
            $event instanceof AllSessionsRevokedEvent => $this->logAllSessionsRevoked($event),
            default => null, // @codeCoverageIgnore
        };
    }

    /**
     * @return array<string>
     *
     * @psalm-return list{
     *   UserSignedInEvent::class,
     *   SignInFailedEvent::class,
     *   AccountLockedOutEvent::class,
     *   TwoFactorCompletedEvent::class,
     *   TwoFactorFailedEvent::class,
     *   RefreshTokenRotatedEvent::class,
     *   RefreshTokenTheftDetectedEvent::class,
     *   AllSessionsRevokedEvent::class
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
            RefreshTokenRotatedEvent::class,
            RefreshTokenTheftDetectedEvent::class,
            AllSessionsRevokedEvent::class,
        ];
    }

    private function logSignedIn(UserSignedInEvent $event): void
    {
        $this->logger->info('User signed in', [
            'userId' => $event->userId,
            'email' => $event->email,
            'sessionId' => $event->sessionId,
            'ipAddress' => $event->ipAddress,
            'userAgent' => $event->userAgent,
        ]);
    }

    private function logFailed(SignInFailedEvent $event): void
    {
        $this->logger->warning('Sign-in failed', [
            'email' => $event->email,
            'ipAddress' => $event->ipAddress,
            'userAgent' => $event->userAgent,
        ]);
    }

    private function logLocked(AccountLockedOutEvent $event): void
    {
        $this->logger->warning('Account locked out', [
            'email' => $event->email,
            'ipAddress' => $event->ipAddress,
            'userAgent' => $event->userAgent,
        ]);
    }

    private function logTfaOk(TwoFactorCompletedEvent $event): void
    {
        $this->logger->info('Two-factor completed', [
            'userId' => $event->userId,
            'sessionId' => $event->sessionId,
            'ipAddress' => $event->ipAddress,
            'userAgent' => $event->userAgent,
            'method' => $event->method,
        ]);
    }

    private function logTfaFail(TwoFactorFailedEvent $event): void
    {
        $this->logger->warning('Two-factor failed', [
            'pendingSessionId' => $event->pendingSessionId,
            'ipAddress' => $event->ipAddress,
            'reason' => $event->reason,
        ]);
    }

    private function logRefreshTokenRotated(
        RefreshTokenRotatedEvent $event
    ): void {
        $this->logger->debug('Refresh token rotated', [
            'sessionId' => $event->sessionId,
            'userId' => $event->userId,
        ]);
    }

    private function logRefreshTokenTheft(
        RefreshTokenTheftDetectedEvent $event
    ): void {
        $this->logger->critical('Refresh token theft detected', [
            'sessionId' => $event->sessionId,
            'userId' => $event->userId,
            'ipAddress' => $event->ipAddress,
            'reason' => $event->reason,
        ]);
    }

    private function logAllSessionsRevoked(
        AllSessionsRevokedEvent $event
    ): void {
        $this->logger->notice('All sessions revoked', [
            'userId' => $event->userId,
            'reason' => $event->reason,
            'revokedCount' => $event->revokedCount,
        ]);
    }
}
