<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\Command\SignInCommand;

/**
 * @psalm-api
 */
final class SignInCommandFactory implements SignInCommandFactoryInterface
{
    #[\Override]
    public function create(
        string $email,
        string $password,
        bool $rememberMe,
        string $ipAddress,
        string $userAgent,
    ): SignInCommand {
        return new SignInCommand($email, $password, $rememberMe, $ipAddress, $userAgent);
    }
}
