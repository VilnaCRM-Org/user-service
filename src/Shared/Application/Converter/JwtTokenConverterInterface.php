<?php

declare(strict_types=1);

namespace App\Shared\Application\Converter;

interface JwtTokenConverterInterface
{
    /**
     * @return array<string, array<int, string>|bool|float|int|string|null>|null null if decoding fails
     */
    public function decode(string $token): ?array;
}
