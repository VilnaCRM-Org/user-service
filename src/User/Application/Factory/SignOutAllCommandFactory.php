<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\Command\SignOutAllCommand;

final class SignOutAllCommandFactory implements SignOutAllCommandFactoryInterface
{
    #[\Override]
    public function create(string $userId): SignOutAllCommand
    {
        return new SignOutAllCommand($userId);
    }
}
