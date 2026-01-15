<?php

declare(strict_types=1);

namespace App\User\Application\EventSubscriber;

use App\Shared\Infrastructure\Cache\CacheKeyBuilder;
use App\User\Domain\Event\EmailChangedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final readonly class EmailChangedCacheInvalidationSubscriber implements
    UserCacheInvalidationSubscriberInterface
{
    public function __construct(
        private TagAwareCacheInterface $cache,
        private CacheKeyBuilder $cacheKeyBuilder,
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(EmailChangedEvent $event): void
    {
        $user = $event->user;
        $this->cache->invalidateTags([
            'user.' . $user->getId(),
            'user.email.' . $this->cacheKeyBuilder->hashEmail($user->getEmail()),
            'user.email.' . $this->cacheKeyBuilder->hashEmail($event->oldEmail),
            'user.email',
        ]);

        $this->logger->info('Cache invalidated after email change', [
            'event_id' => $event->eventId(),
            'operation' => 'cache.invalidation',
            'reason' => 'email_changed',
        ]);
    }

    /**
     * @return array<class-string>
     */
    #[\Override]
    public function subscribedTo(): array
    {
        return [EmailChangedEvent::class];
    }
}
