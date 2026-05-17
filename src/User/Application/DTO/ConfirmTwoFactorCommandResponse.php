<?php

declare(strict_types=1);

namespace App\User\Application\DTO;

use App\Shared\Domain\Bus\Command\CommandResponseInterface;

final readonly class ConfirmTwoFactorCommandResponse implements
    CommandResponseInterface
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
