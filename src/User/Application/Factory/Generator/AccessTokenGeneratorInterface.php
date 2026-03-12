<?php

declare(strict_types=1);

namespace App\User\Application\Factory\Generator;

interface AccessTokenGeneratorInterface
{
    /**
     * @param array<string, int|string|array<string>> $payload
     */
    public function generate(array $payload): string;
}
