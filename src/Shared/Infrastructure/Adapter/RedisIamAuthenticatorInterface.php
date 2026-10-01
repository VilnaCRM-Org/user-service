<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Adapter;

interface RedisIamAuthenticatorInterface
{
    /**
     * @return int Unix time until which the token sent with AUTH is valid
     */
    public function authenticate(\Redis $client): int;
}
