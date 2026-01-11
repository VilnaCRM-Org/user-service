<?php

declare(strict_types=1);

namespace App\User\Domain\Factory;

use App\User\Domain\Aggregate\PasswordResetEmail;
use App\User\Domain\Aggregate\PasswordResetEmailInterface;
use App\User\Domain\Entity\PasswordResetTokenInterface;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Factory\Event\PasswordResetEmailSendEventFactoryInterface;

final readonly class PasswordResetEmailFactory implements
    PasswordResetEmailFactoryInterface
{
    public function __construct(
        private PasswordResetEmailSendEventFactoryInterface $factory,
    ) {
    }

    #[\Override]
    public function create(
        PasswordResetTokenInterface $token,
        UserInterface $user,
    ): PasswordResetEmailInterface {
        return new PasswordResetEmail($token, $user, $this->factory);
    }
}
