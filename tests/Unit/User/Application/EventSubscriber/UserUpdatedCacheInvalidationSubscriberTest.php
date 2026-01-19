<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\EventSubscriber;

use App\Shared\Infrastructure\Cache\CacheKeyBuilder;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\EventSubscriber\UserCacheInvalidationSubscriberInterface;
use App\User\Application\EventSubscriber\UserUpdatedCacheInvalidationSubscriber;
use App\User\Domain\Event\UserUpdatedEvent;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final class UserUpdatedCacheInvalidationSubscriberTest extends UnitTestCase
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

        $this->subscriber = new UserUpdatedCacheInvalidationSubscriber(
            $this->cache,
            $this->cacheKeyBuilder,
            $this->logger
        );
    }

    public function testSubscribedToReturnsCorrectEvents(): void
    {
        $subscribedEvents = $this->subscriber->subscribedTo();

        self::assertCount(1, $subscribedEvents);
        self::assertContains(UserUpdatedEvent::class, $subscribedEvents);
    }

    public function testInvokeInvalidatesCacheWithPreviousEmail(): void
    {
        $userId = $this->faker->uuid();
        $email = $this->faker->email();
        $previousEmail = $this->faker->email();
        $emailHash = 'email_hash_1';
        $previousHash = 'email_hash_2';

        $event = new UserUpdatedEvent(
            $userId,
            $email,
            $previousEmail,
            $this->faker->uuid()
        );

        $this->cacheKeyBuilder
            ->expects($this->exactly(2))
            ->method('hashEmail')
            ->willReturnCallback(
                $this->expectSequential(
                    [[$email], [$previousEmail]],
                    [$emailHash, $previousHash]
                )
            );

        $this->cache
            ->expects($this->once())
            ->method('invalidateTags')
            ->with([
                'user.collection',
                'user.' . $userId,
                'user.email.' . $emailHash,
                'user.email.' . $previousHash,
            ]);

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'Cache invalidated after user update',
                $this->callback(
                    static fn ($context) => $context['operation'] === 'cache.invalidation'
                        && $context['reason'] === 'user_updated'
                        && isset($context['event_id'])
                )
            );

        ($this->subscriber)($event);
    }

    public function testInvokeInvalidatesCacheWithoutPreviousEmail(): void
    {
        $userId = $this->faker->uuid();
        $email = $this->faker->email();
        $emailHash = 'email_hash_1';

        $event = new UserUpdatedEvent(
            $userId,
            $email,
            null,
            $this->faker->uuid()
        );

        $this->cacheKeyBuilder
            ->expects($this->once())
            ->method('hashEmail')
            ->with($email)
            ->willReturn($emailHash);

        $this->cache
            ->expects($this->once())
            ->method('invalidateTags')
            ->with([
                'user.collection',
                'user.' . $userId,
                'user.email.' . $emailHash,
            ]);

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'Cache invalidated after user update',
                $this->callback(
                    static fn ($context) => $context['operation'] === 'cache.invalidation'
                        && $context['reason'] === 'user_updated'
                        && isset($context['event_id'])
                )
            );

        ($this->subscriber)($event);
    }
}
