<?php

declare(strict_types=1);

namespace App\User\Application\Resolver;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class UserPatchEmailResolver
{
    public function resolve(
        ?string $candidate,
        string $fallback,
        bool $provided
    ): string {
        if (!$provided || $candidate === null) {
            return $fallback;
        }

        $trimmed = trim($candidate);
        $this->ensureNotBlank($trimmed);

        return $this->normalizeEmail($trimmed);
    }

    private function ensureNotBlank(string $email): void
    {
        if ($email === '') {
            throw new BadRequestHttpException('email must not be blank.');
        }
    }

    private function normalizeEmail(string $email): string
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return $email;
        }

        return strtolower($email);
    }
}
