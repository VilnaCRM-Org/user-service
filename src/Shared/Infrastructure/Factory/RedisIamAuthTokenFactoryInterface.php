<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Factory;

interface RedisIamAuthTokenFactoryInterface
{
    public function create(): string;
}
