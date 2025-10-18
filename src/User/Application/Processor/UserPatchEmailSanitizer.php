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
        $this->validateNotBlank($trimmed);

        return $this->normalizeEmail($trimmed);
    }

    private function validateNotBlank(string $email): void
    {
        if ($email === '') {
            $this->blankEmail();
        }
    }

    private function normalizeEmail(string $email): string
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return $email;
        }

        return strtolower($email);
    }

    private function blankEmail(): never
    {
        throw new BadRequestHttpException('email must not be blank.');
    }
}
