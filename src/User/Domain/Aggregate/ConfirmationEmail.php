<?php

declare(strict_types=1);

namespace App\User\Domain\Aggregate;

use App\Shared\Domain\Aggregate\AggregateRoot;
use App\User\Domain\Entity\ConfirmationTokenInterface;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Factory\Event\ConfirmationEmailSendEventFactoryInterface;

final class ConfirmationEmail extends AggregateRoot implements
    ConfirmationEmailInterface
{
    public function __construct(
        public readonly ConfirmationTokenInterface $token,
        public readonly UserInterface $user,
        private readonly ConfirmationEmailSendEventFactoryInterface $factory,
    ) {
    }

    public function send(string $eventID): void
    {
        $this->token->send();
        $this->record(
            $this->factory->create(
                $this->token,
                $this->user,
                $eventID
            )
        );
    }
}
