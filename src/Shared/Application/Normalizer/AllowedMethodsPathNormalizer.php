<?php

declare(strict_types=1);

namespace App\Shared\Application\Normalizer;

final readonly class AllowedMethodsPathNormalizer
{
    public function normalize(string $path): string
    {
        $normalized = ltrim($path, '/');

        return str_starts_with($normalized, 'api/')
            ? substr($normalized, 4)
            : $normalized;
    }
}
