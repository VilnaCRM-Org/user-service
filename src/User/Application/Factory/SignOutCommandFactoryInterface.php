<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\Command\SignOutCommand;

interface SignOutCommandFactoryInterface
{
    public function create(string $sessionId, string $userId): SignOutCommand;
}
