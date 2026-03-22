<?php

declare(strict_types=1);

namespace App\User\Application\Resolver;

final class UserPatchPasswordResolver
{
    public function resolve(
        ?string $candidate,
        string $fallback,
        bool $provided
    ): string {
        if (!$provided || $candidate === null) {
            return $fallback;
        }

        return trim($candidate);
    }
}
