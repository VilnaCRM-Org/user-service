<?php

declare(strict_types=1);

namespace App\User\Application\Processor;

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
