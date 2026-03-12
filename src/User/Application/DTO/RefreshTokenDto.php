<?php

declare(strict_types=1);

namespace App\User\Application\DTO;

use LogicException;

final readonly class RefreshTokenDto
{
    public function __construct(
        public string|int|null $refreshToken = '',
    ) {
    }

    public function refreshTokenValue(): string
    {
        return $this->validatedStringValue($this->refreshToken, 'refreshToken');
    }

    private function validatedStringValue(
        string|int|null $value,
        string $field
    ): string {
        if (is_string($value)) {
            return $value;
        }

        throw new LogicException(
            sprintf(
                'Expected "%s" to be a string after request validation.',
                $field
            )
        );
    }
}
