<?php

declare(strict_types=1);

namespace App\User\Domain\Aggregate;

use App\Shared\Domain\Aggregate\AggregateRoot;
use App\User\Domain\Entity\ConfirmationTokenInterface;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Factory\Event\ConfirmationEmailSendEventFactoryInterface;
use App\User\Domain\Factory\Event\PasswordResetRequestedEventFactoryInterface;

final class ConfirmationEmail extends AggregateRoot implements
    ConfirmationEmailInterface
{
    public function __construct(
        public readonly ConfirmationTokenInterface $token,
        public readonly UserInterface $user,
    ) {
    }

    public function send(
        string $eventID,
        ConfirmationEmailSendEventFactoryInterface $eventFactory
    ): void {
        $this->token->send();
        $this->record(
            $eventFactory->create(
                $this->token,
                $this->user,
                $eventID
            )
        );
    }

    public function sendPasswordReset(
        string $eventID,
        PasswordResetRequestedEventFactoryInterface $eventFactory
    ): void {
        $this->token->send();
        $this->record(
            $eventFactory->create(
                $this->token,
                $this->user,
                $eventID
            )
        );
    }
}
