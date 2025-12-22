<?php

declare(strict_types=1);

namespace App\User\Application\Sanitizer;

final class UserPatchPasswordSanitizer
{
    public function sanitize(
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
