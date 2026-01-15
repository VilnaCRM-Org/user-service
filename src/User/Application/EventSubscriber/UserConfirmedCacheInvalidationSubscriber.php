<?php

declare(strict_types=1);

namespace App\User\Application\EventSubscriber;

use App\User\Domain\Event\UserConfirmedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

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
