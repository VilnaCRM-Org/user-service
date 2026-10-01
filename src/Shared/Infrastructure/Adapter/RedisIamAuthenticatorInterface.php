<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Adapter;

interface RedisIamAuthenticatorInterface
{
    public function authenticate(\Redis $client): void;
}
