<?php

declare(strict_types=1);

namespace App\User\Application\Resolver;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class UserPatchFieldResolver
{
    public function resolve(
        ?string $candidate,
        string $fallback,
        bool $provided,
        string $field
    ): string {
        if (!$provided || $candidate === null) {
            return $fallback;
        }

        return $this->trimAndEnsureNotBlank($candidate, $field);
    }

    private function trimAndEnsureNotBlank(
        string $candidate,
        string $field
    ): string {
        $trimmed = trim($candidate);

        if ($trimmed === '') {
            throw new BadRequestHttpException(
                sprintf('%s must not be blank.', $field)
            );
        }

        return $trimmed;
    }
}
