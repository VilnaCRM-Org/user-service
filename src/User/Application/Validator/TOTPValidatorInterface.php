<?php

declare(strict_types=1);

namespace App\User\Application\Validator;

interface TOTPValidatorInterface
{
    public function verify(
        string $secret,
        string $code,
        ?int $timestamp = null
    ): bool;
}
