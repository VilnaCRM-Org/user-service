<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\Command\SignOutAllCommand;

interface SignOutAllCommandFactoryInterface
{
    public function create(string $userId): SignOutAllCommand;
}
