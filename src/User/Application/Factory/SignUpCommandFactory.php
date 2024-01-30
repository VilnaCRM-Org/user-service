<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\Command\SignUpCommand;

final class SignUpCommandFactory implements SignUpCommandFactoryInterface
{
    public function create(
        string $email,
        string $initials,
        string $password
    ): SignUpCommand {
        return new SignUpCommand($email, $initials, $password);
    }
}
