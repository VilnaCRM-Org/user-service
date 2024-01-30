<?php

declare(strict_types=1);

namespace App\User\Domain\Aggregate;

use App\Shared\Domain\Aggregate\AggregateRoot;
use App\User\Domain\Entity\ConfirmationTokenInterface;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Factory\Event\ConfirmationEmailSendEventFactoryInterface;

final class ConfirmationEmail extends AggregateRoot
{
    public function __construct(
        public readonly ConfirmationTokenInterface $token,
        public readonly UserInterface $user,
        private ConfirmationEmailSendEventFactoryInterface $eventFactory,
    ) {
    }

    public function send(string $eventID): void
    {
        $this->token->incrementTimesSent();
        $this->record(
            $this->eventFactory->create(
                $this->token,
                $this->user,
                $eventID
            )
        );
    }
}
