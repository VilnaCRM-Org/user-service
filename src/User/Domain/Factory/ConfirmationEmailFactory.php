<?php

declare(strict_types=1);

namespace App\User\Domain\Factory;

use App\User\Domain\Aggregate\ConfirmationEmail;
use App\User\Domain\Aggregate\ConfirmationEmailInterface;
use App\User\Domain\Entity\ConfirmationTokenInterface;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Factory\Event\ConfirmationEmailSendEventFactoryInterface;

final readonly class ConfirmationEmailFactory implements
    ConfirmationEmailFactoryInterface
{
    public function __construct(
        private ConfirmationEmailSendEventFactoryInterface $factory,
    ) {
    }

    public function create(
        ConfirmationTokenInterface $token,
        UserInterface $user,
    ): ConfirmationEmailInterface {
        return new ConfirmationEmail($token, $user, $this->factory);
    }
}
