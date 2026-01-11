<?php

declare(strict_types=1);

namespace App\User\Application\DTO;

final readonly class UserPatchPayload
{
    public function __construct(private ?array $payload)
    {
    }

    public function provided(string $field): bool
    {
        return $this->payload !== null
            && array_key_exists($field, $this->payload);
    }
}
