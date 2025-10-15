<?php

declare(strict_types=1);

namespace App\User\Application\Processor;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class UserPatchNonEmptySanitizer
{
    public function sanitize(
        ?string $candidate,
        string $fallback,
        bool $provided,
        string $field
    ): string {
        if (!$provided || $candidate === null) {
            return $fallback;
        }

        $trimmed = trim($candidate);

        if ($trimmed === '') {
            $this->blankField($field);
        }

        return $trimmed;
    }

    private function blankField(string $field): never
    {
        throw new BadRequestHttpException(
            sprintf('%s must not be blank.', $field)
        );
    }
}
