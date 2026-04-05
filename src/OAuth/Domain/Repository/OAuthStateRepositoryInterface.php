<?php

declare(strict_types=1);

namespace App\OAuth\Domain\Repository;

use App\OAuth\Domain\ValueObject\OAuthStatePayload;

interface OAuthStateRepositoryInterface
{
    public function save(
        string $state,
        OAuthStatePayload $payload,
        int $ttlSeconds,
    ): void;

    public function validateAndConsume(
        string $state,
        string $provider,
        string $flowBinding,
    ): OAuthStatePayload;
}
