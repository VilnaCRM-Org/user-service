<?php

declare(strict_types=1);

namespace App\User\Application\Message;

use App\User\Domain\Entity\UserInterface;

final readonly class UserRegisteredMessage
{
    public function __construct(public UserInterface $user)
    {
    }
}
