<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Provider;

use App\User\Application\Provider\AccountLockoutProviderInterface;
use App\User\Infrastructure\Provider\Exception\AccountLockoutStorageException;
use Redis;

final readonly class RedisAccountLockoutProvider implements
    AccountLockoutProviderInterface
{
    private const ATTEMPT_WINDOW_SECONDS = 3600;

    /**
     * Atomically increments the failure counter and, on every request that is
     * at or above the threshold, (re)applies the lock. Keeping the
     * read-modify-write in a single server-side script removes the lost-update
     * race that let concurrent failed logins exceed the intended attempt cap.
     *
     * The lock is refreshed (not just set on the exact crossing attempt) so
     * that once the shorter lock TTL expires while the longer attempt window is
     * still open, any further over-threshold failure re-persists the lockout
     * instead of silently letting it lapse.
     *
     * KEYS[1] attempts counter key
     * KEYS[2] lock key
     * ARGV[1] attempt window TTL (seconds)
     * ARGV[2] max attempts threshold
     * ARGV[3] lockout TTL (seconds)
     *
     * Returns 1 when the account is locked, 0 otherwise.
     */
    private const RECORD_FAILURE_LUA_SCRIPT = <<<'LUA'
        local attempts = redis.call('INCR', KEYS[1])
        if attempts == 1 then
            redis.call('EXPIRE', KEYS[1], ARGV[1])
        end
        local maxAttempts = tonumber(ARGV[2])
        if attempts < maxAttempts then
            return 0
        end
        redis.call('SET', KEYS[2], '1', 'EX', ARGV[3])
        return 1
        LUA;

    public function __construct(
        private Redis $lockoutRedis
    ) {
    }

    #[\Override]
    public function isLocked(string $email): bool
    {
        return (bool) $this->lockoutRedis->exists($this->lockKey($email));
    }

    #[\Override]
    public function recordFailure(string $email): bool
    {
        /** @var int|string|false $locked */
        $locked = $this->lockoutRedis->eval(
            self::RECORD_FAILURE_LUA_SCRIPT,
            [
                $this->attemptsKey($email),
                $this->lockKey($email),
                (string) self::ATTEMPT_WINDOW_SECONDS,
                (string) AccountLockoutProviderInterface::MAX_ATTEMPTS,
                (string) AccountLockoutProviderInterface::LOCKOUT_SECONDS,
            ],
            2,
        );

        if ($locked === false) {
            throw new AccountLockoutStorageException();
        }

        return (int) $locked === 1;
    }

    #[\Override]
    public function clearFailures(string $email): void
    {
        $this->lockoutRedis->del(
            $this->attemptsKey($email),
            $this->lockKey($email),
        );
    }

    #[\Override]
    public function maxAttempts(): int
    {
        return AccountLockoutProviderInterface::MAX_ATTEMPTS;
    }

    #[\Override]
    public function lockoutSeconds(): int
    {
        return AccountLockoutProviderInterface::LOCKOUT_SECONDS;
    }

    private function attemptsKey(string $email): string
    {
        return sprintf('signin_lockout_%s', $this->hashedEmailKey($email));
    }

    private function lockKey(string $email): string
    {
        return sprintf('signin_lock_%s', $this->hashedEmailKey($email));
    }

    private function hashedEmailKey(string $email): string
    {
        return hash('sha256', $email);
    }
}
