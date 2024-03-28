<?php

declare(strict_types=1);

namespace App\User\Domain\Factory\Event;

use App\User\Domain\Entity\ConfirmationToken;
use App\User\Domain\Event\UserConfirmedEvent;

interface UserConfirmedEventFactoryInterface
{
    public function create(
        ConfirmationToken $token,
        string $eventId
    ): UserConfirmedEvent;
}
