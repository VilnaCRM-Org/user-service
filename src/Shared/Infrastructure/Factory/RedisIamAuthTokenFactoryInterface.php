<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Factory;

use App\Shared\Infrastructure\Adapter\RedisIamAuthToken;

interface RedisIamAuthTokenFactoryInterface
{
    public function create(): RedisIamAuthToken;
}
