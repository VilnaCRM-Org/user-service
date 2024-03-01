<?php

declare(strict_types=1);

namespace App\User\Domain\Factory\Event;

use App\User\Domain\Entity\ConfirmationTokenInterface;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Event\ConfirmationEmailSentEvent;

final class ConfirmationEmailSendEventFactory implements
    ConfirmationEmailSendEventFactoryInterface
{
    public function create(
        ConfirmationTokenInterface $token,
        UserInterface $user,
        string $eventID,
    ): ConfirmationEmailSentEvent {
        return new ConfirmationEmailSentEvent(
            $token,
            $user->getEmail(),
            $eventID,
        );
    }
}
