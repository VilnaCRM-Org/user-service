<?php

declare(strict_types=1);

namespace App\User\Application\Factory\Event;

use App\User\Domain\Entity\PasswordResetTokenInterface;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Event\PasswordResetEmailSentEvent;
use App\User\Domain\Factory\Event\PasswordResetEmailSendEventFactoryInterface;

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
            $token->getTokenValue(),
            $token->getUserID(),
            $user->getEmail(),
            $eventID,
        );
    }
}
