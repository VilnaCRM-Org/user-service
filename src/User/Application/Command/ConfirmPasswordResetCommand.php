<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\Shared\Domain\Bus\Command\CommandInterface;
use App\User\Domain\Entity\PasswordResetTokenInterface;

final readonly class ConfirmPasswordResetCommand implements CommandInterface
{
    public function __construct(
        public PasswordResetTokenInterface $token,
        public string $newPassword
    ) {
    }
}