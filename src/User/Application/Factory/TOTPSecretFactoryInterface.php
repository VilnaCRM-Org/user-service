<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

interface TOTPSecretFactoryInterface
{
    /**
     * @return array{secret: string, otpauth_uri: string}
     */
    public function create(string $email): array;
}
