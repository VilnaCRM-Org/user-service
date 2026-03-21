<?php

declare(strict_types=1);

namespace App\User\Application\DTO;

final readonly class ConfirmTwoFactorCommandResponse
{
    /**
     * @param array<string> $recoveryCodes
     */
    public function __construct(
        private array $recoveryCodes,
    ) {
    }

    /**
     * @return array<string>
     */
    public function getRecoveryCodes(): array
    {
        return $this->recoveryCodes;
    }
}
