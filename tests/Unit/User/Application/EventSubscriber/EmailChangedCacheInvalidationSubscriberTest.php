<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\EventSubscriber;

use App\Shared\Infrastructure\Cache\CacheKeyBuilder;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\EventSubscriber\EmailChangedCacheInvalidationSubscriber;
use App\User\Application\EventSubscriber\UserCacheInvalidationSubscriberInterface;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Event\EmailChangedEvent;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final class EmailChangedCacheInvalidationSubscriberTest extends UnitTestCase
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

        $this->subscriber = new EmailChangedCacheInvalidationSubscriber(
            $this->cache,
            $this->cacheKeyBuilder,
            $this->logger
        );
    }

    public function testSubscribedToReturnsCorrectEvents(): void
    {
        $subscribedEvents = $this->subscriber->subscribedTo();

        self::assertCount(1, $subscribedEvents);
        self::assertContains(EmailChangedEvent::class, $subscribedEvents);
    }

    public function testInvokeInvalidatesCacheForBothOldAndNewEmail(): void
    {
        $userId = $this->faker->uuid();
        $newEmail = $this->faker->email();
        $oldEmail = $this->faker->email();
        $newEmailHash = 'new_email_hash_123';
        $oldEmailHash = 'old_email_hash_456';

        $user = $this->createMock(UserInterface::class);
        $user->method('getId')->willReturn($userId);
        $user->method('getEmail')->willReturn($newEmail);

        $event = new EmailChangedEvent(
            $user,
            $oldEmail,
            $this->faker->uuid()
        );

        $this->cacheKeyBuilder
            ->expects($this->exactly(2))
            ->method('hashEmail')
            ->willReturnCallback(
                static fn (string $email) => match ($email) {
                    $newEmail => $newEmailHash,
                    $oldEmail => $oldEmailHash,
                    default => throw new \InvalidArgumentException('Unexpected email')
                }
            );

        $this->cache
            ->expects($this->once())
            ->method('invalidateTags')
            ->with([
                'user.' . $userId,
                'user.email.' . $newEmailHash,
                'user.email.' . $oldEmailHash,
                'user.email',
            ]);

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'Cache invalidated after email change',
                $this->callback(
                    static fn ($context) => $context['operation'] === 'cache.invalidation'
                        && $context['reason'] === 'email_changed'
                        && isset($context['event_id'])
                )
            );

        ($this->subscriber)($event);
    }
}
