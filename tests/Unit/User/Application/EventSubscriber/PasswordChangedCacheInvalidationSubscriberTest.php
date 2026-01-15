<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\EventSubscriber;

use App\Shared\Infrastructure\Cache\CacheKeyBuilder;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\EventSubscriber\PasswordChangedCacheInvalidationSubscriber;
use App\User\Application\EventSubscriber\UserCacheInvalidationSubscriberInterface;
use App\User\Domain\Event\PasswordChangedEvent;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final class PasswordChangedCacheInvalidationSubscriberTest extends UnitTestCase
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

        $this->subscriber = new PasswordChangedCacheInvalidationSubscriber(
            $this->cache,
            $this->cacheKeyBuilder,
            $this->logger
        );
    }

    public function testSubscribedToReturnsCorrectEvents(): void
    {
        $subscribedEvents = $this->subscriber->subscribedTo();

        self::assertCount(1, $subscribedEvents);
        self::assertContains(PasswordChangedEvent::class, $subscribedEvents);
    }

    public function testInvokeInvalidatesCache(): void
    {
        $email = $this->faker->email();
        $emailHash = 'email_hash_789';

        $event = new PasswordChangedEvent(
            $email,
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
                'user.email.' . $emailHash,
            ]);

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'Cache invalidated after password change',
                $this->callback(
                    static fn ($context) => $context['operation'] === 'cache.invalidation'
                        && $context['reason'] === 'password_changed'
                        && isset($context['event_id'])
                )
            );

        ($this->subscriber)($event);
    }
}
