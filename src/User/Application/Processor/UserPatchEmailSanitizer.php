<?php

declare(strict_types=1);

namespace App\User\Application\Processor;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class UserPatchEmailSanitizer
{
    public function sanitize(
        ?string $candidate,
        string $fallback,
        bool $provided
    ): string {
        if (!$provided || $candidate === null) {
            return $fallback;
        }

        $trimmed = trim($candidate);

        if ($trimmed === '') {
            $this->blankEmail();
        }

        if (filter_var($trimmed, FILTER_VALIDATE_EMAIL) === false) {
            return $trimmed;
        }

        return strtolower($trimmed);
    }

    private function blankEmail(): never
    {
        throw new BadRequestHttpException('email must not be blank.');
    }
}
