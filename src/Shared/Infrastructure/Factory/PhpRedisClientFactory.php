<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Factory;

use Redis;

final readonly class PhpRedisClientFactory implements RedisClientFactoryInterface
{
    #[\Override]
    public function create(): Redis
    {
        return new Redis();
    }
}
