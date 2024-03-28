<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\Command\ConfirmUserCommand;
use App\User\Domain\Entity\ConfirmationToken;

interface ConfirmUserCommandFactoryInterface
{
    public function create(ConfirmationToken $token): ConfirmUserCommand;
}
