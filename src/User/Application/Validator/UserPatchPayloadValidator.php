<?php

declare(strict_types=1);

namespace App\User\Application\Validator;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final readonly class UserPatchPayloadValidator
{
    private const IMMUTABLE_FIELDS = ['email', 'initials', 'newPassword'];

    public function ensureNoExplicitNulls(?array $payload): void
    {
        if ($payload === null) {
            return;
        }

        $invalidField = $this->findFirstExplicitlyNullField($payload);

        if ($invalidField !== null) {
            throw new BadRequestHttpException(
                sprintf('%s must not be null.', $invalidField)
            );
        }
    }

    /**
     * @param array<string, scalar|array|null> $payload
     */
    private function findFirstExplicitlyNullField(array $payload): ?string
    {
        foreach (self::IMMUTABLE_FIELDS as $field) {
            if (array_key_exists($field, $payload) && $payload[$field] === null) {
                return $field;
            }
        }

        return null;
    }
}
