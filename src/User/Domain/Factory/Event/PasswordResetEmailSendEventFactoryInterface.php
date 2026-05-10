<?php

declare(strict_types=1);

namespace App\User\Domain\Factory\Event;

use App\User\Domain\Entity\PasswordResetTokenInterface;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Event\PasswordResetEmailSentEvent;

interface PasswordResetEmailSendEventFactoryInterface
{
    public function create(
        PasswordResetTokenInterface $token,
        UserInterface $user,
        string $eventID,
    ): PasswordResetEmailSentEvent;
}
