<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\Shared\Domain\Bus\Command\CommandInterface;
use App\User\Domain\Contract\PasswordResetEmailInterface;

final readonly class SendPasswordResetEmailCommand implements CommandInterface
{
    public function __construct(
        public PasswordResetEmailInterface $passwordResetEmail
    ) {
    }
}
