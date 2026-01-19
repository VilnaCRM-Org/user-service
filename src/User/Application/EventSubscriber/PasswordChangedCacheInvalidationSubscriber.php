<?php

declare(strict_types=1);

namespace App\User\Application\EventSubscriber;

use App\Shared\Infrastructure\Cache\CacheKeyBuilder;
use App\User\Domain\Event\PasswordChangedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * Password Changed Event Cache Invalidation Subscriber
 *
 * Invalidates cache when a user changes their password.
 *
 * ARCHITECTURAL DECISION: Processed via async queue (AsyncSymfonyEventBus)
 * This subscriber runs in Symfony Messenger workers. Exceptions propagate to
 * DomainEventMessageHandler which catches, logs, and emits failure metrics.
 * We follow AP from CAP theorem (Availability + Partition tolerance over Consistency).
 */
final readonly class PasswordChangedCacheInvalidationSubscriber implements
    UserCacheInvalidationSubscriberInterface
{
    public function __construct(
        private TagAwareCacheInterface $cache,
        private CacheKeyBuilder $cacheKeyBuilder,
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(PasswordChangedEvent $event): void
    {
        $this->cache->invalidateTags([
            'user.email.' . $this->cacheKeyBuilder->hashEmail($event->email),
        ]);

        $this->logger->info('Cache invalidated after password change', [
            'event_id' => $event->eventId(),
            'operation' => 'cache.invalidation',
            'reason' => 'password_changed',
        ]);
    }

    /**
     * @return array<class-string>
     */
    #[\Override]
    public function subscribedTo(): array
    {
        return [PasswordChangedEvent::class];
    }
}
