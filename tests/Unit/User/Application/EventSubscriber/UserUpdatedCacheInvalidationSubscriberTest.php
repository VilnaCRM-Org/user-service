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
        $email = $this->faker->unique()->email();
        $previousEmail = $this->faker->unique()->email();
        $emailHash = $this->faker->sha256();
        $previousHash = $this->faker->sha256();
        $event = $this->createEvent($userId, $email, $previousEmail);
        $this->expectEmailHashes(
            [$email, $previousEmail],
            [$emailHash, $previousHash]
        );
        $this->assertCacheInvalidation($event, [
            'user.collection',
            'user.' . $userId,
            'user.email.' . $emailHash,
            'user.email.' . $previousHash,
        ]);
    }

    public function testInvokeInvalidatesCacheWithoutPreviousEmail(): void
    {
        $userId = $this->faker->uuid();
        $email = $this->faker->unique()->email();
        $emailHash = $this->faker->sha256();
        $event = $this->createEvent($userId, $email, null);
        $this->expectEmailHash($email, $emailHash);
        $this->assertCacheInvalidation($event, [
            'user.collection',
            'user.' . $userId,
            'user.email.' . $emailHash,
        ]);
    }

    private function expectCacheInvalidationLog(): void
    {
        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'Cache invalidated after user update',
                $this->callback(
                    static function (array $context): bool {
                        return $context['operation'] === 'cache.invalidation'
                            && $context['reason'] === 'user_updated'
                            && isset($context['event_id']);
                    }
                )
            );
    }

    private function createEvent(
        string $userId,
        string $email,
        ?string $previousEmail
    ): UserUpdatedEvent {
        return new UserUpdatedEvent(
            $userId,
            $email,
            $previousEmail,
            $this->faker->uuid()
        );
    }

    /**
     * @param array<int, string> $emails
     * @param array<int, string> $hashes
     */
    private function expectEmailHashes(array $emails, array $hashes): void
    {
        $expectedCalls = array_map(
            static fn (string $email): array => [$email],
            $emails
        );

        $this->cacheKeyBuilder
            ->expects($this->exactly(count($emails)))
            ->method('hashEmail')
            ->willReturnCallback($this->expectSequential($expectedCalls, $hashes));
    }

    private function expectEmailHash(string $email, string $hash): void
    {
        $this->cacheKeyBuilder
            ->expects($this->once())
            ->method('hashEmail')
            ->with($email)
            ->willReturn($hash);
    }

    /**
     * @param array<int, string> $tags
     */
    private function assertCacheInvalidation(UserUpdatedEvent $event, array $tags): void
    {
        $this->cache
            ->expects($this->once())
            ->method('invalidateTags')
            ->with($tags);

        $this->expectCacheInvalidationLog();
        ($this->subscriber)($event);
    }
}
