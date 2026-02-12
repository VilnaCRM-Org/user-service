<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Service;

use App\Tests\Unit\UnitTestCase;
use App\User\Infrastructure\Service\RedisAccountLockoutService;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

final class RedisAccountLockoutServiceTest extends UnitTestCase
{
    private CacheItemPoolInterface&MockObject $cachePool;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->cachePool = $this->createMock(CacheItemPoolInterface::class);
    }

    public function testIsLockedReturnsTrueWhenLockItemExists(): void
    {
        $emailHash = hash('sha256', 'test@example.com');

        $lockItem = $this->createMock(CacheItemInterface::class);
        $lockItem
            ->expects($this->once())
            ->method('isHit')
            ->willReturn(true);

        $this->cachePool
            ->expects($this->once())
            ->method('getItem')
            ->with(sprintf('signin_lock_%s', $emailHash))
            ->willReturn($lockItem);

        $service = new RedisAccountLockoutService($this->cachePool);

        $this->assertTrue($service->isLocked(' Test@Example.COM '));
    }

    public function testRecordFailureReturnsFalseBeforeThreshold(): void
    {
        $emailHash = hash('sha256', 'test@example.com');

        $attemptItem = $this->createMock(CacheItemInterface::class);
        $attemptItem
            ->expects($this->once())
            ->method('get')
            ->willReturn(3);

        $attemptItem
            ->expects($this->once())
            ->method('set')
            ->with(4)
            ->willReturnSelf();

        $attemptItem
            ->expects($this->once())
            ->method('expiresAfter')
            ->with(3600)
            ->willReturnSelf();

        $this->cachePool
            ->expects($this->once())
            ->method('getItem')
            ->with(sprintf('signin_lockout_%s', $emailHash))
            ->willReturn($attemptItem);

        $this->cachePool
            ->expects($this->once())
            ->method('save')
            ->with($attemptItem)
            ->willReturn(true);

        $service = new RedisAccountLockoutService($this->cachePool);

        $this->assertFalse($service->recordFailure(' Test@Example.COM '));
    }

    public function testRecordFailureReturnsTrueWhenThresholdReached(): void
    {
        $emailHash = hash('sha256', 'test@example.com');

        $attemptItem = $this->createMock(CacheItemInterface::class);
        $attemptItem
            ->expects($this->once())
            ->method('get')
            ->willReturn(19);

        $attemptItem
            ->expects($this->once())
            ->method('set')
            ->with(20)
            ->willReturnSelf();

        $attemptItem
            ->expects($this->once())
            ->method('expiresAfter')
            ->with(3600)
            ->willReturnSelf();

        $lockItem = $this->createMock(CacheItemInterface::class);
        $lockItem
            ->expects($this->once())
            ->method('set')
            ->with(true)
            ->willReturnSelf();

        $lockItem
            ->expects($this->once())
            ->method('expiresAfter')
            ->with(900)
            ->willReturnSelf();

        $requestedKeys = [];
        $this->cachePool
            ->expects($this->exactly(2))
            ->method('getItem')
            ->willReturnCallback(static function (string $key) use ($attemptItem, $lockItem, &$requestedKeys): \PHPUnit\Framework\MockObject\MockObject&\Psr\Cache\CacheItemInterface {
                $requestedKeys[] = $key;

                return count($requestedKeys) === 1 ? $attemptItem : $lockItem;
            });

        $savedItems = [];
        $this->cachePool
            ->expects($this->exactly(2))
            ->method('save')
            ->willReturnCallback(/**
             * @return true
             */
            static function (CacheItemInterface $item) use (&$savedItems): bool {
                $savedItems[] = $item;

                return true;
            });

        $service = new RedisAccountLockoutService($this->cachePool);

        $this->assertTrue($service->recordFailure(' Test@Example.COM '));
        $this->assertSame(
            [
                sprintf('signin_lockout_%s', $emailHash),
                sprintf('signin_lock_%s', $emailHash),
            ],
            $requestedKeys
        );
        $this->assertSame([$attemptItem, $lockItem], $savedItems);
    }

    public function testClearFailuresDeletesAttemptAndLockKeys(): void
    {
        $emailHash = hash('sha256', 'test@example.com');

        $this->cachePool
            ->expects($this->once())
            ->method('deleteItems')
            ->with([
                sprintf('signin_lockout_%s', $emailHash),
                sprintf('signin_lock_%s', $emailHash),
            ])
            ->willReturn(true);

        $service = new RedisAccountLockoutService($this->cachePool);

        $service->clearFailures(' Test@Example.COM ');
    }

    public function testRecordFailureTreatsEmptyAttemptCounterAsZero(): void
    {
        $emailHash = hash('sha256', 'test@example.com');

        $attemptItem = $this->createMock(CacheItemInterface::class);
        $attemptItem
            ->expects($this->once())
            ->method('get')
            ->willReturn('');

        $attemptItem
            ->expects($this->once())
            ->method('set')
            ->with(1)
            ->willReturnSelf();

        $attemptItem
            ->expects($this->once())
            ->method('expiresAfter')
            ->with(3600)
            ->willReturnSelf();

        $this->cachePool
            ->expects($this->once())
            ->method('getItem')
            ->with(sprintf('signin_lockout_%s', $emailHash))
            ->willReturn($attemptItem);

        $this->cachePool
            ->expects($this->once())
            ->method('save')
            ->with($attemptItem)
            ->willReturn(true);

        $service = new RedisAccountLockoutService($this->cachePool);

        $this->assertFalse($service->recordFailure(' Test@Example.COM '));
    }
}
