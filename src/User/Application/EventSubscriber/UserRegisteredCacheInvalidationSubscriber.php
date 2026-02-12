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
 * @see docs/ADR/0001-async-cache-invalidation.md for architectural decisions
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
        $this->cache->invalidateTags([
            'user.collection',
            'user.' . $event->userId,
            'user.email.' . $this->cacheKeyBuilder->hashEmail($event->email),
        ]);

        $this->logger->info('Cache invalidated after user registration', [
            'event_id' => $event->eventId(),
            'operation' => 'cache.invalidation',
            'reason' => 'user_registered',
        ]);
    }

    /**
     * @return string[]
     *
     * @psalm-return list{UserRegisteredEvent::class}
     */
    #[\Override]
    public function subscribedTo(): array
    {
        return [UserRegisteredEvent::class];
    }
}
