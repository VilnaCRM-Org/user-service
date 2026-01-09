<?php

declare(strict_types=1);

namespace App\User\Application\Validator;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final readonly class UserPatchPayloadValidator
{
    private const IMMUTABLE_FIELDS = ['email', 'initials', 'newPassword'];

    public function ensureNoExplicitNulls(?array $payload): void
    {
        $invalidField = $this->findInvalidField($payload);

        if ($invalidField === null) {
            return;
        }

        throw new BadRequestHttpException(
            sprintf('%s must not be null.', $invalidField)
        );
    }

    private function findInvalidField(?array $payload): ?string
    {
        if ($payload === null) {
            return null;
        }

        foreach (self::IMMUTABLE_FIELDS as $field) {
            if ($this->fieldIsExplicitlyNull($payload, $field)) {
                return $field;
            }
        }

        return null;
    }

    /**
     * @param array<string, scalar|array|null> $payload
     */
    private function fieldIsExplicitlyNull(array $payload, string $field): bool
    {
        return array_key_exists($field, $payload) && $payload[$field] === null;
    }
}
