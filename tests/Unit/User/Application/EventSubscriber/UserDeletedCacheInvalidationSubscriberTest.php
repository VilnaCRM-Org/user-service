<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\EventSubscriber;

use App\Shared\Infrastructure\Cache\CacheKeyBuilder;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\EventSubscriber\UserCacheInvalidationSubscriberInterface;
use App\User\Application\EventSubscriber\UserDeletedCacheInvalidationSubscriber;
use App\User\Domain\Event\UserDeletedEvent;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final class UserDeletedCacheInvalidationSubscriberTest extends UnitTestCase
{
    private TagAwareCacheInterface&MockObject $cache;
    private CacheKeyBuilder&MockObject $cacheKeyBuilder;
    private LoggerInterface&MockObject $logger;
    private UserCacheInvalidationSubscriberInterface $subscriber;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = $this->createMock(TagAwareCacheInterface::class);
        $this->cacheKeyBuilder = $this->createMock(CacheKeyBuilder::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->subscriber = new UserDeletedCacheInvalidationSubscriber(
            $this->cache,
            $this->cacheKeyBuilder,
            $this->logger
        );
    }

    public function testSubscribedToReturnsCorrectEvents(): void
    {
        $subscribedEvents = $this->subscriber->subscribedTo();

        self::assertCount(1, $subscribedEvents);
        self::assertContains(UserDeletedEvent::class, $subscribedEvents);
    }

    public function testInvokeInvalidatesCache(): void
    {
        $userId = $this->faker->uuid();
        $email = $this->faker->email();
        $emailHash = 'email_hash_123';
        $event = $this->createEvent($userId, $email);

        $this->expectEmailHash($email, $emailHash);
        $this->assertCacheInvalidation($event, $userId, $emailHash);
    }

    private function createEvent(string $userId, string $email): UserDeletedEvent
    {
        return new UserDeletedEvent(
            $userId,
            $email,
            $this->faker->uuid()
        );
    }

    private function expectEmailHash(string $email, string $emailHash): void
    {
        $this->cacheKeyBuilder
            ->expects($this->once())
            ->method('hashEmail')
            ->with($email)
            ->willReturn($emailHash);
    }

    private function assertCacheInvalidation(
        UserDeletedEvent $event,
        string $userId,
        string $emailHash
    ): void {
        $this->cache
            ->expects($this->once())
            ->method('invalidateTags')
            ->with([
                'user.collection',
                'user.' . $userId,
                'user.email.' . $emailHash,
            ]);

        $this->expectInvalidationLog();
        ($this->subscriber)($event);
    }

    private function expectInvalidationLog(): void
    {
        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'Cache invalidated after user deletion',
                $this->callback(
                    static function (array $context): bool {
                        return $context['operation'] === 'cache.invalidation'
                            && $context['reason'] === 'user_deleted'
                            && isset($context['event_id']);
                    }
                )
            );
    }
}
