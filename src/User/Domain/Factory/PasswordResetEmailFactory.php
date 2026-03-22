<?php

declare(strict_types=1);

namespace App\User\Domain\Factory;

use App\User\Domain\Aggregate\PasswordResetEmail;
use App\User\Domain\Aggregate\PasswordResetEmailInterface;
use App\User\Domain\Entity\PasswordResetTokenInterface;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Factory\Event\PasswordResetEmailSendEventFactoryInterface;
use InvalidArgumentException;

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
        if ($token->getUserID() !== $user->getId()) {
            throw new InvalidArgumentException(
                sprintf(
                    'Token user ID "%s" does not match user ID "%s"',
                    $token->getUserID(),
                    $user->getId()
                )
            );
        }

        return new PasswordResetEmail($token, $user, $this->factory);
    }
}
