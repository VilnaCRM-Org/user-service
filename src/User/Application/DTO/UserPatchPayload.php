<?php

declare(strict_types=1);

namespace App\User\Application\DTO;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final readonly class UserPatchPayload
{
    private const IMMUTABLE_FIELDS = ['email', 'initials', 'newPassword'];

    public function __construct(private ?array $payload)
    {
    }

    public function ensureNoExplicitNulls(): void
    {
        $invalidField = $this->findInvalidField();

        if ($invalidField === null) {
            return;
        }

        throw new BadRequestHttpException(
            sprintf('%s must not be null.', $invalidField)
        );
    }

    public function provided(string $field): bool
    {
        return $this->payload !== null
            && array_key_exists($field, $this->payload);
    }

    private function findInvalidField(): ?string
    {
        return match (true) {
            $this->payload === null => null,
            ($field = array_search(
                null,
                array_intersect_key(
                    $this->payload,
                    array_flip(self::IMMUTABLE_FIELDS)
                ),
                true
            )) === false => null,
            default => (string) $field,
        };
    }
}
