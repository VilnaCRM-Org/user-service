<?php

declare(strict_types=1);

namespace App\User\Application\DTO;

use LogicException;

final readonly class CompleteTwoFactorDto
{
    public function __construct(
        public string|int|null $pendingSessionId = '',
        public string|int|null $twoFactorCode = '',
    ) {
    }

    public function pendingSessionIdValue(): string
    {
        return $this->validatedStringValue($this->pendingSessionId, 'pendingSessionId');
    }

    public function twoFactorCodeValue(): string
    {
        return $this->validatedStringValue($this->twoFactorCode, 'twoFactorCode');
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
