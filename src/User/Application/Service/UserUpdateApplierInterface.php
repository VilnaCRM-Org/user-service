<?php

declare(strict_types=1);

namespace App\User\Application\Service;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\ValueObject\UserUpdate;

interface UserUpdateApplierInterface
{
    /**
     * @return array<int, DomainEvent>
     */
    public function apply(
        UserInterface $user,
        UserUpdate $updateData,
        string $hashedPassword,
        string $eventId
    ): array;
}
