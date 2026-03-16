<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Validator;

use App\User\Application\Validator\AccountLockoutValidatorInterface;
use Psr\Cache\CacheItemPoolInterface;

final readonly class RedisAccountLockout implements
    AccountLockoutValidatorInterface
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

        if ($attempts < AccountLockoutValidatorInterface::MAX_ATTEMPTS) {
            return false;
        }

        $lockItem = $this->cachePool->getItem($this->lockKey($email));
        $lockItem->set(true);
        $lockItem->expiresAfter(AccountLockoutValidatorInterface::LOCKOUT_SECONDS);
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

    #[\Override]
    public function maxAttempts(): int
    {
        return self::MAX_ATTEMPTS;
    }

    #[\Override]
    public function lockoutSeconds(): int
    {
        return self::LOCKOUT_SECONDS;
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
