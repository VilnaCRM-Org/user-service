<?php

declare(strict_types=1);

namespace App\User\Domain\Factory\Event;

use App\User\Domain\Entity\ConfirmationTokenInterface;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Event\ConfirmationEmailSendEvent;

interface ConfirmationEmailSendEventFactoryInterface
{
    public function create(
        ConfirmationTokenInterface $token,
        UserInterface $user,
        string $eventID,
    ): ConfirmationEmailSendEvent;
}
