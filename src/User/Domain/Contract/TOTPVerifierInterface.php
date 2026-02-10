<?php

declare(strict_types=1);

namespace App\User\Domain\Contract;

interface TOTPVerifierInterface
{
    public function verify(
        string $secret,
        string $code,
        ?int $timestamp = null
    ): bool;
}
