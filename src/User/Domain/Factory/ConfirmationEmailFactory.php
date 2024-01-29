<?php

declare(strict_types=1);

namespace App\User\Domain\Factory;

use App\User\Domain\Aggregate\ConfirmationEmail;
use App\User\Domain\Entity\ConfirmationTokenInterface;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Factory\Event\ConfirmationEmailSendEventFactory;

final class ConfirmationEmailFactory
{
    public function __construct(
        private ConfirmationEmailSendEventFactory $factory,
    ) {
    }

    public function create(
        ConfirmationTokenInterface $token,
        UserInterface $user,
    ): ConfirmationEmail {
        return new ConfirmationEmail($token, $user, $this->factory);
    }
}
