<?php

declare(strict_types=1);

namespace App\Shared\Application\Provider;

use App\Shared\Domain\ValueObject\JwtVerificationKey;

interface JwtVerificationKeyProviderInterface
{
    /**
     * The key that signs new tokens (JWT_KMS_KEY_ID).
     */
    public function current(): JwtVerificationKey;

    /**
     * The current key, then the previous key during a key-change window.
     *
     * @return list<JwtVerificationKey>
     */
    public function verificationKeys(): array;

    public function findByKid(string $kid): ?JwtVerificationKey;
}
