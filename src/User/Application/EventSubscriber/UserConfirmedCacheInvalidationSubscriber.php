<?php

declare(strict_types=1);

namespace App\User\Application\EventSubscriber;

use App\User\Domain\Event\UserConfirmedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * User Confirmed Event Cache Invalidation Subscriber
 *
 * Invalidates cache when a user confirms their account.
 *
 * ARCHITECTURAL DECISION: Processed via async queue (AsyncSymfonyEventBus)
 * This subscriber runs in Symfony Messenger workers. Exceptions propagate to
 * DomainEventMessageHandler which catches, logs, and emits failure metrics.
 * We follow AP from CAP theorem (Availability + Partition tolerance over Consistency).
 */
final readonly class UserConfirmedCacheInvalidationSubscriber implements
    UserCacheInvalidationSubscriberInterface
{
    public function __construct(
        private TagAwareCacheInterface $cache,
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(UserConfirmedEvent $event): void
    {
        $userId = $event->token->getUserID();
        $this->cache->invalidateTags([
            'user.' . $userId,
        ]);

        $this->logger->info('Cache invalidated after user confirmation', [
            'event_id' => $event->eventId(),
            'operation' => 'cache.invalidation',
            'reason' => 'user_confirmed',
        ]);
    }

    /**
     * @return array<class-string>
     */
    #[\Override]
    public function subscribedTo(): array
    {
        return [UserConfirmedEvent::class];
    }
}
