<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\Command\SignOutCommand;

final class SignOutCommandFactory implements SignOutCommandFactoryInterface
{
    #[\Override]
    public function create(string $sessionId, string $userId): SignOutCommand
    {
        return new SignOutCommand($sessionId, $userId);
    }
}
