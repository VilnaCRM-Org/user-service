<?php

declare(strict_types=1);

namespace App\User\Application\EventSubscriber;

use App\Shared\Infrastructure\Cache\CacheKeyBuilder;
use App\User\Domain\Event\UserRegisteredEvent;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * User Registered Event Cache Invalidation Subscriber
 *
 * Invalidates cache when a user is registered.
 *
 * ARCHITECTURAL DECISION: Processed via async queue (AsyncSymfonyEventBus)
 * This subscriber runs in Symfony Messenger workers. Exceptions propagate to
 * DomainEventMessageHandler which catches, logs, and emits failure metrics.
 * We follow AP from CAP theorem (Availability + Partition tolerance over Consistency).
 */
final readonly class UserRegisteredCacheInvalidationSubscriber implements
    UserCacheInvalidationSubscriberInterface
{
    public function __construct(
        private TagAwareCacheInterface $cache,
        private CacheKeyBuilder $cacheKeyBuilder,
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(UserRegisteredEvent $event): void
    {
        $user = $event->user;
        $this->cache->invalidateTags([
            'user.collection',
            'user.' . $user->getId(),
            'user.email.' . $this->cacheKeyBuilder->hashEmail($user->getEmail()),
        ]);

        $this->logger->info('Cache invalidated after user registration', [
            'event_id' => $event->eventId(),
            'operation' => 'cache.invalidation',
            'reason' => 'user_registered',
        ]);
    }

    /**
     * @return array<class-string>
     */
    #[\Override]
    public function subscribedTo(): array
    {
        return [UserRegisteredEvent::class];
    }
}
