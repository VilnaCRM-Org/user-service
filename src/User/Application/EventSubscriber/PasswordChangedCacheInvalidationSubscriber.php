<?php

declare(strict_types=1);

namespace App\User\Application\EventSubscriber;

use App\Shared\Infrastructure\Cache\CacheKeyBuilder;
use App\User\Domain\Event\PasswordChangedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

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
