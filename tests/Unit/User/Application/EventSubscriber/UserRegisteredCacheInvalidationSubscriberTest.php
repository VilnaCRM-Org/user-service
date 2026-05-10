<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\EventSubscriber;

use App\Shared\Infrastructure\Cache\CacheKeyBuilder;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\EventSubscriber\UserCacheInvalidationSubscriberInterface;
use App\User\Application\EventSubscriber\UserRegisteredCacheInvalidationSubscriber;
use App\User\Domain\Event\UserRegisteredEvent;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final class UserRegisteredCacheInvalidationSubscriberTest extends UnitTestCase
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

        $this->subscriber = new UserRegisteredCacheInvalidationSubscriber(
            $this->cache,
            $this->cacheKeyBuilder,
            $this->logger
        );
    }

    public function testSubscribedToReturnsCorrectEvents(): void
    {
        $subscribedEvents = $this->subscriber->subscribedTo();

        self::assertCount(1, $subscribedEvents);
        self::assertContains(UserRegisteredEvent::class, $subscribedEvents);
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

    private function createEvent(string $userId, string $email): UserRegisteredEvent
    {
        return new UserRegisteredEvent(
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
        UserRegisteredEvent $event,
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
                'Cache invalidated after user registration',
                $this->callback(
                    static function (array $context): bool {
                        return $context['operation'] === 'cache.invalidation'
                            && $context['reason'] === 'user_registered'
                            && isset($context['event_id']);
                    }
                )
            );
    }
}
