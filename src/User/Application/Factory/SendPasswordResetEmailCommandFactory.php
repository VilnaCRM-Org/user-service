<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\Command\SendPasswordResetEmailCommand;
use App\User\Domain\Aggregate\PasswordResetEmailInterface;

final class SendPasswordResetEmailCommandFactory implements
    SendPasswordResetEmailCommandFactoryInterface
{
    #[\Override]
    public function create(
        PasswordResetEmailInterface $passwordResetEmail
    ): SendPasswordResetEmailCommand {
        return new SendPasswordResetEmailCommand($passwordResetEmail);
    }
}
