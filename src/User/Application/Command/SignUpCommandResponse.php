<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\Shared\Domain\Bus\Command\CommandResponse;
use App\User\Domain\Entity\User;

readonly class SignUpCommandResponse implements CommandResponse
{
    public function __construct(public User $createdUser)
    {
    }
}
