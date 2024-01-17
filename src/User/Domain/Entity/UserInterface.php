<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\User\Domain\Event\UserConfirmedEvent;

interface UserInterface
{
    public function confirm(ConfirmationToken $token): UserConfirmedEvent;

    /**
     * @return array<DomainEvent>
     */
    public function update(
        string $newEmail,
        string $newInitials,
        string $newPassword,
        string $oldPassword,
        string $hashedNewPassword
    ): array;
}
