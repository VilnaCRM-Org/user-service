<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\User\Domain\Event\UserConfirmedEvent;
use App\User\Domain\Factory\Event\EmailChangedEventFactoryInterface;
use App\User\Domain\Factory\Event\PasswordChangedEventFactoryInterface;
use App\User\Domain\Factory\Event\UserConfirmedEventFactoryInterface;
use App\User\Domain\ValueObject\UserUpdate;

interface UserInterface
{
    public function confirm(
        ConfirmationToken $token,
        string $eventID,
        UserConfirmedEventFactoryInterface $userConfirmedEventFactory
    ): UserConfirmedEvent;

    /**
     * @return array<DomainEvent>
     */
    public function update(
        UserUpdate $updateData,
        string $hashedNewPassword,
        string $eventID,
        EmailChangedEventFactoryInterface $emailChangedEventFactory,
        PasswordChangedEventFactoryInterface $passwordChangedEventFactory,
    ): array;

    /**
     * @return array<DomainEvent>
     */
    public function updatePassword(
        string $hashedPassword,
        string $eventID,
        PasswordChangedEventFactoryInterface $passwordChangedEventFactory
    ): array;

    public function getId(): string;

    public function getEmail(): string;

    public function isConfirmed(): bool;
}
