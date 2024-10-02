<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\User\Domain\Event\UserConfirmedEvent;
use App\User\Domain\Factory\Event\EmailChangedEventFactoryInterface;
use App\User\Domain\Factory\Event\PasswordChangedEventFactoryInterface;
use App\User\Domain\Factory\Event\UserConfirmedEventFactoryInterface;
use App\User\Domain\ValueObject\UserUpdate;
use Symfony\Component\Uid\Factory\UuidFactory;

interface UserInterface
{
    public function confirm(
        ConfirmationToken $token,
        UuidFactory $uuidFactory,
        UserConfirmedEventFactoryInterface $userConfirmedEventFactory
    ): UserConfirmedEvent;

    /**
     * @return array<DomainEvent>
     */
    public function update(
        UserUpdate $updateData,
        string $hashedNewPassword,
        UuidFactory $uuidFactory,
        EmailChangedEventFactoryInterface $emailChangedEventFactory,
        PasswordChangedEventFactoryInterface $passwordChangedEventFactory,
    ): array;

    /**
     * @return array<DomainEvent>
     */
    public function updatePassword(
        string $hashedPassword,
        UuidFactory $uuidFactory,
        PasswordChangedEventFactoryInterface $passwordChangedEventFactory
    ): array;

    public function getId(): string;

    public function getEmail(): string;

    public function isConfirmed(): bool;
}
