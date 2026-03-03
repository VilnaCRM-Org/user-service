<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Lockout;

use App\Tests\Unit\UnitTestCase;
use App\User\Infrastructure\Lockout\RedisAccountLockout;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

final class RedisAccountLockoutTest extends UnitTestCase
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

        $service = new RedisAccountLockout($this->cachePool);

        $this->assertTrue($service->isLocked('test@example.com'));
    }

    public function testRecordFailureReturnsFalseBeforeThreshold(): void
    {
        $emailHash = hash('sha256', 'test@example.com');
        $attemptItem = $this->createAttemptItem(3, 4);
        $this->expectCachePoolGetAndSave(
            sprintf('signin_lockout_%s', $emailHash),
            $attemptItem
        );
        $service = new RedisAccountLockout($this->cachePool);

        $this->assertFalse($service->recordFailure('test@example.com'));
    }

    public function testRecordFailureReturnsTrueWhenThresholdReached(): void
    {
        $emailHash = hash('sha256', 'test@example.com');
        $attemptItem = $this->createAttemptItem(19, 20);
        $lockItem = $this->createLockItem();
        $requestedKeys = [];
        $savedItems = [];
        $this->expectSequentialGetItems($attemptItem, $lockItem, $requestedKeys);
        $this->expectSequentialSaves($savedItems);
        $service = new RedisAccountLockout($this->cachePool);
        $this->assertTrue($service->recordFailure('test@example.com'));
        $expectedKeys = [
            sprintf('signin_lockout_%s', $emailHash),
            sprintf('signin_lock_%s', $emailHash),
        ];
        $this->assertSame($expectedKeys, $requestedKeys);
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

        $service = new RedisAccountLockout($this->cachePool);

        $service->clearFailures('test@example.com');
    }

    public function testRecordFailureTreatsEmptyAttemptCounterAsZero(): void
    {
        $emailHash = hash('sha256', 'test@example.com');
        $attemptItem = $this->createAttemptItem('', 1);
        $this->expectCachePoolGetAndSave(
            sprintf('signin_lockout_%s', $emailHash),
            $attemptItem
        );
        $service = new RedisAccountLockout($this->cachePool);

        $this->assertFalse($service->recordFailure('test@example.com'));
    }

    private function createAttemptItem(
        mixed $currentCount,
        int $newCount
    ): CacheItemInterface&MockObject {
        $item = $this->createMock(CacheItemInterface::class);
        $item->expects($this->once())->method('get')->willReturn($currentCount);
        $item->expects($this->once())->method('set')->with($newCount)->willReturnSelf();
        $item->expects($this->once())->method('expiresAfter')->with(3600)->willReturnSelf();

        return $item;
    }

    private function createLockItem(): CacheItemInterface&MockObject
    {
        $item = $this->createMock(CacheItemInterface::class);
        $item->expects($this->once())->method('set')->with(true)->willReturnSelf();
        $item->expects($this->once())->method('expiresAfter')->with(900)->willReturnSelf();

        return $item;
    }

    private function expectCachePoolGetAndSave(
        string $key,
        CacheItemInterface $item
    ): void {
        $this->cachePool
            ->expects($this->once())
            ->method('getItem')
            ->with($key)
            ->willReturn($item);
        $this->cachePool
            ->expects($this->once())
            ->method('save')
            ->with($item)
            ->willReturn(true);
    }

    /**
     * @param array<string> $requestedKeys
     */
    private function expectSequentialGetItems(
        CacheItemInterface $firstItem,
        CacheItemInterface $secondItem,
        array &$requestedKeys
    ): void {
        $this->cachePool
            ->expects($this->exactly(2))
            ->method('getItem')
            ->willReturnCallback(
                static function (
                    string $key
                ) use (
                    $firstItem,
                    $secondItem,
                    &$requestedKeys
                ): MockObject&CacheItemInterface {
                    $requestedKeys[] = $key;

                    return count($requestedKeys) === 1
                        ? $firstItem : $secondItem;
                }
            );
    }

    /**
     * @param array<CacheItemInterface> $savedItems
     */
    private function expectSequentialSaves(array &$savedItems): void
    {
        $this->cachePool
            ->expects($this->exactly(2))
            ->method('save')
            ->willReturnCallback(/**
             * @return true
             */
                static function (CacheItemInterface $item) use (&$savedItems): bool {
                    $savedItems[] = $item;

                    return true;
                }
            );
    }
}
