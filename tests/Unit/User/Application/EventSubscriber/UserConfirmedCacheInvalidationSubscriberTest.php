<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\EventSubscriber;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\EventSubscriber\UserCacheInvalidationSubscriberInterface;
use App\User\Application\EventSubscriber\UserConfirmedCacheInvalidationSubscriber;
use App\User\Domain\Entity\ConfirmationToken;
use App\User\Domain\Event\UserConfirmedEvent;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final class UserConfirmedCacheInvalidationSubscriberTest extends UnitTestCase
{
    private TagAwareCacheInterface&MockObject $cache;
    private LoggerInterface&MockObject $logger;
    private UserCacheInvalidationSubscriberInterface $subscriber;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = $this->createMock(TagAwareCacheInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->subscriber = new UserConfirmedCacheInvalidationSubscriber(
            $this->cache,
            $this->logger
        );
    }

    public function testSubscribedToReturnsCorrectEvents(): void
    {
        $subscribedEvents = $this->subscriber->subscribedTo();

        self::assertCount(1, $subscribedEvents);
        self::assertContains(UserConfirmedEvent::class, $subscribedEvents);
    }

    public function testInvokeInvalidatesCache(): void
    {
        $userId = $this->faker->uuid();
        $tokenValue = $this->faker->lexify('??????????');

        $token = new ConfirmationToken($tokenValue, $userId);

        $event = new UserConfirmedEvent(
            $token,
            $this->faker->uuid()
        );

        $this->cache
            ->expects($this->once())
            ->method('invalidateTags')
            ->with([
                'user.' . $userId,
            ]);

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'Cache invalidated after user confirmation',
                $this->callback(
                    static fn ($context) => $context['operation'] === 'cache.invalidation'
                        && $context['reason'] === 'user_confirmed'
                        && isset($context['event_id'])
                )
            );

        ($this->subscriber)($event);
    }
}
