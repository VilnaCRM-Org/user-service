<?php

declare(strict_types=1);

namespace App\User\Domain\Aggregate;

use App\User\Domain\Factory\Event\ConfirmationEmailSendEventFactoryInterface;
use App\User\Domain\Factory\Event\PasswordResetRequestedEventFactoryInterface;

interface ConfirmationEmailInterface
{
    public function send(
        string $eventID,
        ConfirmationEmailSendEventFactoryInterface $eventFactory
    ): void;

    public function sendPasswordReset(
        string $eventID,
        PasswordResetRequestedEventFactoryInterface $eventFactory
    ): void;
}
