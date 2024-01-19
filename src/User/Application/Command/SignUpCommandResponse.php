<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\Shared\Domain\Bus\Command\CommandResponseInterface;
use App\User\Domain\Entity\User;

final readonly class SignUpCommandResponse implements CommandResponseInterface
{
    public function __construct(public User $createdUser)
    {
    }
}
