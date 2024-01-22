<?php

declare(strict_types=1);

namespace App\User\Domain\Aggregate;

use App\Shared\Domain\Aggregate\AggregateRoot;
use App\User\Domain\Entity\ConfirmationTokenInterface;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Event\ConfirmationEmailSendEvent;

final class ConfirmationEmail extends AggregateRoot
{
    public function __construct(
        public readonly ConfirmationTokenInterface $token,
        public readonly UserInterface $user
    ) {
    }

    public function send(string $eventID): void
    {
        $this->token->incrementTimesSent();
        $this->record(
            new ConfirmationEmailSendEvent(
                $this->token,
                $this->user->getEmail(),
                $eventID
            )
        );
    }
}
