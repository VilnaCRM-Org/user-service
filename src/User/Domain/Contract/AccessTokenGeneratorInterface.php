<?php

declare(strict_types=1);

namespace App\User\Domain\Contract;

interface AccessTokenGeneratorInterface
{
    /**
     * @param array<string, int|string|array<string>> $payload
     */
    public function generate(array $payload): string;
}
