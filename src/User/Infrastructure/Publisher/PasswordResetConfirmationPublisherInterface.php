<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Publisher;

use App\User\Domain\Entity\UserInterface;

interface PasswordResetConfirmationPublisherInterface
{
    public function publish(UserInterface $user): void;
}
