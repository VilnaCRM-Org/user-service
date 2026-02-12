<?php

declare(strict_types=1);

namespace App\User\Domain\Contract;

interface TOTPSecretGeneratorInterface
{
    /**
     * @return array{secret: string, otpauth_uri: string}
     */
    public function generate(string $email): array;
}
