<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Factory;

interface RedisClientFactoryInterface
{
    public function create(): \Redis;
}
