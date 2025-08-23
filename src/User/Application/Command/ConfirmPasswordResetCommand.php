<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\Shared\Domain\Bus\Command\CommandInterface;
use App\User\Domain\Entity\PasswordResetToken;

final readonly class ConfirmPasswordResetCommand implements CommandInterface
{
    public function __construct(
        public PasswordResetToken $token,
        public string $newPassword
    ) {
    }
}