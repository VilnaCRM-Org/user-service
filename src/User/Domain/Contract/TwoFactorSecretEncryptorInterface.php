<?php

declare(strict_types=1);

namespace App\User\Domain\Contract;

interface TwoFactorSecretEncryptorInterface
{
    public function encrypt(string $secret): string;

    public function decrypt(string $payload): string;
}
