<?php

declare(strict_types=1);

namespace App\OAuth\Infrastructure\EventSubscriber;

use App\OAuth\Domain\Event\OAuthUserCreatedEvent;
use App\OAuth\Domain\Event\OAuthUserSignedInEvent;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use Psr\Log\LoggerInterface;

final readonly class OAuthEventLogSubscriber implements DomainEventSubscriberInterface
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function __invoke(object $event): void
    {
        if ($event instanceof OAuthUserCreatedEvent) {
            $this->logUserCreated($event);
        } elseif ($event instanceof OAuthUserSignedInEvent) {
            $this->logUserSignedIn($event);
        }
    }

    /**
     * @return array<string>
     *
     * @psalm-return list{OAuthUserCreatedEvent::class, OAuthUserSignedInEvent::class}
     */
    #[\Override]
    public function subscribedTo(): array
    {
        return [
            OAuthUserCreatedEvent::class,
            OAuthUserSignedInEvent::class,
        ];
    }

    private function logUserCreated(OAuthUserCreatedEvent $event): void
    {
        $this->logger->info('OAuth user created', [
            'event' => 'oauth.user_created',
            'userId' => $event->userId,
            'email' => $event->email,
            'provider' => $event->provider,
            'timestamp' => $event->occurredOn(),
        ]);
    }

    private function logUserSignedIn(OAuthUserSignedInEvent $event): void
    {
        $this->logger->info('OAuth user signed in', [
            'event' => 'oauth.user_signed_in',
            'userId' => $event->userId,
            'email' => $event->email,
            'provider' => $event->provider,
            'sessionId' => $event->sessionId,
            'timestamp' => $event->occurredOn(),
        ]);
    }
}
