<?php

declare(strict_types=1);

namespace App\User\Domain\Factory;

use App\User\Domain\Aggregate\ConfirmationEmail;
use App\User\Domain\Aggregate\ConfirmationEmailInterface;
use App\User\Domain\Entity\ConfirmationTokenInterface;
use App\User\Domain\Entity\UserInterface;

final readonly class ConfirmationEmailFactory implements
    ConfirmationEmailFactoryInterface
{
    public function create(
        ConfirmationTokenInterface $token,
        UserInterface $user,
    ): ConfirmationEmailInterface {
        return new ConfirmationEmail($token, $user);
    }
}
