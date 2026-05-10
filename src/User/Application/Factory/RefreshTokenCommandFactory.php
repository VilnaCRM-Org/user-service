<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\Command\RefreshTokenCommand;

final class RefreshTokenCommandFactory implements RefreshTokenCommandFactoryInterface
{
    #[\Override]
    public function create(string $refreshToken): RefreshTokenCommand
    {
        return new RefreshTokenCommand($refreshToken);
    }
}
