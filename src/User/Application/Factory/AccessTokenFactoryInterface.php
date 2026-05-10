<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

interface AccessTokenFactoryInterface
{
    /**
     * @param array<string, int|string|array<string>> $payload
     */
    public function create(array $payload): string;
}
