<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Factory;

interface RedisConnectionFactoryInterface
{
    public function create(string $dsn): \Redis;
}
