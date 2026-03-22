<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\Command\RegisterUserCommand;

final class SignUpCommandFactory implements SignUpCommandFactoryInterface
{
    #[\Override]
    public function create(
        string $email,
        string $initials,
        string $password
    ): RegisterUserCommand {
        return new RegisterUserCommand($email, $initials, $password);
    }
}
