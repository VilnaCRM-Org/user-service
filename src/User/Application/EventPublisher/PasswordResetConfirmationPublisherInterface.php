<?php

declare(strict_types=1);

namespace App\User\Application\EventPublisher;

use App\User\Domain\Entity\UserInterface;

interface PasswordResetConfirmationPublisherInterface
{
    public function publish(UserInterface $user): void;
}
