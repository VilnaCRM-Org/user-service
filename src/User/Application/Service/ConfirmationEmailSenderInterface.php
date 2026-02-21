<?php

declare(strict_types=1);

namespace App\User\Application\Service;

use App\User\Domain\Entity\User;

interface ConfirmationEmailSenderInterface
{
    /**
     * Resolves or creates a confirmation token for the user, then dispatches
     * the confirmation email command.
     */
    public function send(User $user): void;
}
