<?php

declare(strict_types=1);

namespace App\User\Domain\Factory\Event;

use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Event\PasswordResetRequestedEvent;

final class PasswordResetRequestedEventFactory implements
    PasswordResetRequestedEventFactoryInterface
{
    #[\Override]
    public function create(
        UserInterface $user,
        string $token,
        string $eventId
    ): PasswordResetRequestedEvent {
        return new PasswordResetRequestedEvent(
            $user->getId(),
            $user->getEmail(),
            $token,
            $eventId
        );
    }
}
