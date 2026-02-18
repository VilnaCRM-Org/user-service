<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Service;

use App\User\Domain\Contract\AccountLockoutServiceInterface;
use Psr\Cache\CacheItemPoolInterface;

final readonly class RedisAccountLockoutService implements
    AccountLockoutServiceInterface
{
    private const ATTEMPT_WINDOW_SECONDS = 3600;

    public function __construct(
        private CacheItemPoolInterface $cachePool
    ) {
    }

    #[\Override]
    public function isLocked(string $email): bool
    {
        return $this->cachePool->getItem($this->lockKey($email))->isHit();
    }

    #[\Override]
    public function recordFailure(string $email): bool
    {
        $attemptsItem = $this->cachePool->getItem($this->attemptsKey($email));
        $attempts = (int) $attemptsItem->get() + 1;

        $attemptsItem->set($attempts);
        $attemptsItem->expiresAfter(self::ATTEMPT_WINDOW_SECONDS);
        $this->cachePool->save($attemptsItem);

        if ($attempts < AccountLockoutServiceInterface::MAX_ATTEMPTS) {
            return false;
        }

        $lockItem = $this->cachePool->getItem($this->lockKey($email));
        $lockItem->set(true);
        $lockItem->expiresAfter(AccountLockoutServiceInterface::LOCKOUT_SECONDS);
        $this->cachePool->save($lockItem);

        return true;
    }

    #[\Override]
    public function clearFailures(string $email): void
    {
        $this->cachePool->deleteItems([
            $this->attemptsKey($email),
            $this->lockKey($email),
        ]);
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
        return hash('sha256', $this->normalizeEmail($email));
    }

    private function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }
}
