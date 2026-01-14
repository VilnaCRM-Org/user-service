<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Cache;

final readonly class CacheKeyBuilder
{
    public function build(string $namespace, string ...$parts): string
    {
        return $namespace . '.' . implode('.', $parts);
    }

    public function buildUserKey(string $userId): string
    {
        return $this->build('user', $userId);
    }

    public function buildUserEmailKey(string $email): string
    {
        return $this->build('user', 'email', $this->hashEmail($email));
    }

    public function hashEmail(string $email): string
    {
        return hash('sha256', strtolower($email));
    }
}
