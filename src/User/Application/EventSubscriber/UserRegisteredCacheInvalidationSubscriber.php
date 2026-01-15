<?php

declare(strict_types=1);

namespace App\User\Application\EventSubscriber;

use App\Shared\Infrastructure\Cache\CacheKeyBuilder;
use App\User\Domain\Event\UserRegisteredEvent;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

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
