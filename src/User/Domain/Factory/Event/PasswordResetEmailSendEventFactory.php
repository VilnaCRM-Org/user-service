<?php

declare(strict_types=1);

namespace App\User\Domain\Factory\Event;

use App\User\Domain\Entity\PasswordResetTokenInterface;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Event\PasswordResetEmailSentEvent;

final class PasswordResetEmailSendEventFactory implements
    PasswordResetEmailSendEventFactoryInterface
{
    #[\Override]
    public function create(
        PasswordResetTokenInterface $token,
        UserInterface $user,
        string $eventID,
    ): PasswordResetEmailSentEvent {
        return new PasswordResetEmailSentEvent(
            $token,
            $user->getEmail(),
            $eventID,
        );
    }
}
