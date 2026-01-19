<?php

declare(strict_types=1);

namespace App\User\Application\EventSubscriber;

use App\Shared\Infrastructure\Cache\CacheKeyBuilder;
use App\User\Domain\Event\UserUpdatedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final readonly class UserUpdatedCacheInvalidationSubscriber implements
    UserCacheInvalidationSubscriberInterface
{
    public function __construct(
        private TagAwareCacheInterface $cache,
        private CacheKeyBuilder $cacheKeyBuilder,
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(UserUpdatedEvent $event): void
    {
        $tags = [
            'user.collection',
            'user.' . $event->userId,
            'user.email.' . $this->cacheKeyBuilder->hashEmail($event->email),
        ];

        if (
            $event->previousEmail !== null
            && $event->previousEmail !== $event->email
        ) {
            $tags[] = 'user.email.' . $this->cacheKeyBuilder->hashEmail(
                $event->previousEmail
            );
        }

        $this->cache->invalidateTags($tags);

        $this->logger->info('Cache invalidated after user update', [
            'event_id' => $event->eventId(),
            'operation' => 'cache.invalidation',
            'reason' => 'user_updated',
        ]);
    }

    /**
     * @return array<class-string>
     */
    #[\Override]
    public function subscribedTo(): array
    {
        return [UserUpdatedEvent::class];
    }
}
