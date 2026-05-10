<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use App\Shared\Domain\Collection\DomainEventCollection;
use App\User\Domain\Event\UserConfirmedEvent;
use App\User\Domain\Factory\Event\UserConfirmedEventFactoryInterface;
use App\User\Domain\Factory\Event\UserUpdateEventFactoryInterface;
use App\User\Domain\ValueObject\UserUpdate;

interface UserInterface
{
    public function getId(): string;

    public function getEmail(): string;

    public function getPassword(): string;

    public function setPassword(string $password): void;

    public function upgradePasswordHash(string $newHash): void;

    public function enableTwoFactor(): void;

    public function disableTwoFactor(): void;

    public function confirm(
        ConfirmationToken $token,
        string $eventID,
        UserConfirmedEventFactoryInterface $userConfirmedEventFactory
    ): UserConfirmedEvent;

    public function update(
        UserUpdate $updateData,
        string $hashedNewPassword,
        string $eventID,
        UserUpdateEventFactoryInterface $userUpdateEventFactory,
    ): DomainEventCollection;
}
