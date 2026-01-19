<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Cache;

/**
 * Cache Key Builder Service
 *
 * Responsibilities:
 * - Centralized cache key generation
 * - Consistent email hashing strategy
 * - Eliminates duplication across repository and handlers
 *
 * Usage:
 * - buildUserKey($userId) → "user.{id}"
 * - buildUserEmailKey($email) → "user.email.{hash}"
 */
final readonly class CacheKeyBuilder
{
    /**
     * Build cache key from namespace and parts
     */
    public function build(string $namespace, string ...$parts): string
    {
        return $namespace . '.' . implode('.', $parts);
    }

    /**
     * Build user cache key by ID
     */
    public function buildUserKey(string $userId): string
    {
        return $this->build('user', $userId);
    }

    /**
     * Build user email cache key with hash
     */
    public function buildUserEmailKey(string $email): string
    {
        $emailHash = $this->hashEmail($email);

        return $this->build('user', 'email', $emailHash);
    }

    public function buildUserCollectionKey(array $filters): string
    {
        ksort($filters);

        return $this->build(
            'user',
            'collection',
            hash('sha256', json_encode($filters, JSON_THROW_ON_ERROR))
        );
    }

    /**
     * Hash email consistently (lowercase + SHA256)
     *
     * Strategy:
     * - Lowercase normalization (email case-insensitive)
     * - SHA256 hashing (fixed length, secure)
     * - Prevents cache key length issues
     */
    public function hashEmail(string $email): string
    {
        return hash('sha256', strtolower($email));
    }
}
