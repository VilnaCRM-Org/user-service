<?php

declare(strict_types=1);

namespace App\User\Domain\Aggregate;

use App\Shared\Domain\Aggregate\AggregateRoot;
use App\User\Domain\Entity\PasswordResetTokenInterface;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Factory\Event\PasswordResetEmailSendEventFactoryInterface;

final class PasswordResetEmail extends AggregateRoot implements
    PasswordResetEmailInterface
{
    public function __construct(
        public readonly PasswordResetTokenInterface $token,
        public readonly UserInterface $user,
        private readonly PasswordResetEmailSendEventFactoryInterface $factory,
    ) {
    }

    public function send(string $eventID): void
    {
        $this->record(
            $this->factory->create(
                $this->token,
                $this->user,
                $eventID
            )
        );
    }
}
