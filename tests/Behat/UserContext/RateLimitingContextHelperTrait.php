<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use App\User\Domain\Entity\User;
use RuntimeException;
use Symfony\Component\RateLimiter\RateLimiterFactory;

trait RateLimitingContextHelperTrait
{
    private function requireCurrentUserId(): string
    {
        $currentUserEmail = $this->state->currentUserEmail;
        if (!is_string($currentUserEmail) || $currentUserEmail === '') {
            throw new RuntimeException('Current user is not set.');
        }

        return $this->resolveUserIdByEmail($currentUserEmail);
    }

    private function resolveUserIdByEmail(string $email): string
    {
        $user = $this->userRepository->findByEmail($email);
        if (!$user instanceof User) {
            throw new RuntimeException(
                sprintf('User with email %s was not found.', $email)
            );
        }

        return $user->getId();
    }

    private function consume(
        RateLimiterFactory $limiter,
        string $key,
        int $count
    ): void {
        $limiter->create($key)->consume($count);
    }

    private function limiter(string $name): RateLimiterFactory
    {
        $service = $this->container->get(sprintf('limiter.%s', $name));
        if (!$service instanceof RateLimiterFactory) {
            throw new RuntimeException(sprintf('Rate limiter "%s" is not configured.', $name));
        }

        return $service;
    }

    private function buildUserKey(string $userId): string
    {
        return $this->buildKey('user', $userId);
    }

    private function buildEmailKey(string $email): string
    {
        return $this->buildKey('email', strtolower(trim($email)));
    }

    private function buildIpKey(): string
    {
        return $this->buildKey('ip', self::IP_ADDRESS);
    }

    private function buildKey(string $prefix, string $value): string
    {
        return sprintf('%s:%s', $prefix, $value);
    }
}
