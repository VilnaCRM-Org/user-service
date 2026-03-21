<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\Command\RefreshTokenCommand;

interface RefreshTokenCommandFactoryInterface
{
    public function create(string $refreshToken): RefreshTokenCommand;
}
