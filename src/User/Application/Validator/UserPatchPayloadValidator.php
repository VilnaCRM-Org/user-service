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
        return match (true) {
            $payload === null => null,
            ($field = array_search(
                null,
                array_intersect_key(
                    $payload,
                    array_flip(self::IMMUTABLE_FIELDS)
                ),
                true
            )) === false => null,
            default => (string) $field,
        };
    }
}
