<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Factory;

interface TOTPCreatorInterface
{
    /**
     * @return array{secret: string, otpauth_uri: string}
     */
    public function create(string $label, string $issuer): array;
}
