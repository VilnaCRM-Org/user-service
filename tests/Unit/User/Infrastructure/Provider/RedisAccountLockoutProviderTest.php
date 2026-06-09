<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Provider;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Provider\AccountLockoutProviderInterface;
use App\User\Infrastructure\Provider\RedisAccountLockoutProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Redis;

final class RedisAccountLockoutProviderTest extends UnitTestCase
{
    private const EMAIL = 'test@example.com';
    private const ATTEMPT_WINDOW_SECONDS = 3600;

    private Redis&MockObject $redis;
    private RedisAccountLockoutProvider $service;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->redis = $this->createMock(Redis::class);
        $this->service = new RedisAccountLockoutProvider($this->redis);
    }

    public function testIsLockedReturnsTrueWhenLockKeyExists(): void
    {
        $this->redis
            ->expects($this->once())
            ->method('exists')
            ->with($this->lockKey())
            ->willReturn(1);

        $this->assertTrue($this->service->isLocked(self::EMAIL));
    }

    public function testIsLockedReturnsFalseWhenLockKeyMissing(): void
    {
        $this->redis
            ->expects($this->once())
            ->method('exists')
            ->with($this->lockKey())
            ->willReturn(0);

        $this->assertFalse($this->service->isLocked(self::EMAIL));
    }

    public function testRecordFailureReturnsFalseBeforeThreshold(): void
    {
        $this->expectAtomicEval(0);

        $this->assertFalse($this->service->recordFailure(self::EMAIL));
    }

    public function testRecordFailureReturnsTrueWhenThresholdReached(): void
    {
        $this->expectAtomicEval(1);

        $this->assertTrue($this->service->recordFailure(self::EMAIL));
    }

    /**
     * Regression for the lost-update race (CWE-362): the failure counter must be
     * incremented and the lock applied inside a single atomic Redis eval. The
     * provider must NOT perform a PHP-side read-modify-write (get + set + save),
     * which let concurrent requests read the same value and undercount attempts.
     */
    public function testRecordFailureUsesSingleAtomicEvalWithoutReadModifyWrite(): void
    {
        $capturedArguments = $this->captureEvalArguments(0);

        $this->redis->expects($this->never())->method('get');
        $this->redis->expects($this->never())->method('set');
        $this->redis->expects($this->never())->method('incr');

        $this->service->recordFailure(self::EMAIL);

        [$script, $keys, $numKeys] = $capturedArguments();

        $this->assertStringContainsString('INCR', $script);
        $this->assertStringContainsString('EXPIRE', $script);
        $this->assertSame(2, $numKeys, 'Lua must bind both attempts and lock keys');
        $this->assertSame($this->attemptsKey(), $keys[0]);
        $this->assertSame($this->lockKey(), $keys[1]);
    }

    public function testRecordFailurePassesThresholdAndTtlConfigurationToScript(): void
    {
        $capturedArguments = $this->captureEvalArguments(1);

        $this->service->recordFailure(self::EMAIL);

        [, $keys] = $capturedArguments();

        $this->assertSame(
            (string) self::ATTEMPT_WINDOW_SECONDS,
            $keys[2],
            'Attempt window TTL must be forwarded to the atomic script'
        );
        $this->assertSame(
            (string) AccountLockoutProviderInterface::MAX_ATTEMPTS,
            $keys[3],
            'Max-attempts threshold must be forwarded to the atomic script'
        );
        $this->assertSame(
            (string) AccountLockoutProviderInterface::LOCKOUT_SECONDS,
            $keys[4],
            'Lockout TTL must be forwarded to the atomic script'
        );
    }

    public function testRecordFailureTreatsFalseEvalResultAsNotLocked(): void
    {
        $this->redis->method('eval')->willReturn(false);

        $this->assertFalse($this->service->recordFailure(self::EMAIL));
    }

    public function testClearFailuresDeletesAttemptAndLockKeys(): void
    {
        $this->redis
            ->expects($this->once())
            ->method('del')
            ->with($this->attemptsKey(), $this->lockKey())
            ->willReturn(2);

        $this->service->clearFailures(self::EMAIL);
    }

    public function testMaxAttemptsReturnsConstant(): void
    {
        $this->assertSame(20, $this->service->maxAttempts());
    }

    public function testLockoutSecondsReturnsConstant(): void
    {
        $this->assertSame(900, $this->service->lockoutSeconds());
    }

    private function expectAtomicEval(int $returnValue): void
    {
        $this->redis
            ->expects($this->once())
            ->method('eval')
            ->with(
                $this->isType('string'),
                $this->callback(function (array $keys): bool {
                    return $keys[0] === $this->attemptsKey()
                        && $keys[1] === $this->lockKey();
                }),
                2,
            )
            ->willReturn($returnValue);
    }

    /**
     * @return callable(): array{0: string, 1: list<string>, 2: int}
     */
    private function captureEvalArguments(int $returnValue): callable
    {
        $captured = [];

        $this->redis
            ->expects($this->once())
            ->method('eval')
            ->willReturnCallback(
                static function (
                    string $script,
                    array $keys,
                    int $numKeys
                ) use (&$captured, $returnValue): int {
                    $captured = [$script, $keys, $numKeys];

                    return $returnValue;
                }
            );

        return static function () use (&$captured): array {
            return $captured;
        };
    }

    private function attemptsKey(): string
    {
        return sprintf('signin_lockout_%s', hash('sha256', self::EMAIL));
    }

    private function lockKey(): string
    {
        return sprintf('signin_lock_%s', hash('sha256', self::EMAIL));
    }
}
