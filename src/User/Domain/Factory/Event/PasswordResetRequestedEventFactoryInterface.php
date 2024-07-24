<?php

declare(strict_types=1);

namespace App\User\Domain\Factory\Event;

use App\User\Domain\Entity\ConfirmationTokenInterface;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Event\PasswordResetRequestedEvent;

interface PasswordResetRequestedEventFactoryInterface
{
    public function create(
        ConfirmationTokenInterface $confirmationToken,
        UserInterface $user,
        string $eventId
    ): PasswordResetRequestedEvent;
}
