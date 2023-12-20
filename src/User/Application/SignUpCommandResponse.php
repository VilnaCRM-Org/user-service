<?php

namespace App\User\Application;

use App\Shared\Domain\Bus\Command\CommandResponse;
use App\User\Domain\Entity\User\User;

class SignUpCommandResponse implements CommandResponse
{
    public function __construct(private User $createdUser)
    {
    }

    public function getCreatedUser(): User
    {
        return $this->createdUser;
    }
}
