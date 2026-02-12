<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\ValueObject\UuidInterface;
use App\User\Domain\Event\UserConfirmedEvent;
use App\User\Domain\Factory\Event\EmailChangedEventFactoryInterface;
use App\User\Domain\Factory\Event\PasswordChangedEventFactoryInterface;
use App\User\Domain\Factory\Event\UserConfirmedEventFactoryInterface;
use App\User\Domain\ValueObject\UserUpdate;

class User implements UserInterface
{
    private bool $confirmed;
    private bool $twoFactorEnabled;
    private ?string $twoFactorSecret;

    public function __construct(
        private string $email,
        private string $initials,
        private string $password,
        private UuidInterface $id,
    ) {
        $this->confirmed = false;
        $this->twoFactorEnabled = false;
        $this->twoFactorSecret = null;
    }

    #[\Override]
    public function getId(): string
    {
        return (string) $this->id;
    }

    /**
     * @internal For Doctrine ORM hydration and test fixtures only.
     *
     * @psalm-suppress PossiblyUnusedMethod Doctrine hydration
     */
    public function setId(UuidInterface $id): void
    {
        $this->id = $id;
    }

    #[\Override]
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @internal For Doctrine ORM hydration and test fixtures only. Use update() for business logic.
     *
     * @psalm-suppress PossiblyUnusedMethod Doctrine hydration
     *
     * @see User::update()
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getInitials(): string
    {
        return $this->initials;
    }

    /**
     * @internal For Doctrine ORM hydration and test fixtures only. Use update() for business logic.
     *
     * @psalm-suppress PossiblyUnusedMethod Doctrine hydration
     *
     * @see User::update()
     */
    public function setInitials(string $initials): void
    {
        $this->initials = $initials;
    }

    #[\Override]
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @internal For Doctrine ORM hydration only. Use update() for business logic.
     */
    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    #[\Override]
    public function confirm(
        ConfirmationToken $token,
        string $eventID,
        UserConfirmedEventFactoryInterface $userConfirmedEventFactory
    ): UserConfirmedEvent {
        $this->confirmed = true;

        return $userConfirmedEventFactory->create($token, $eventID);
    }

    /**
     * @return (\App\User\Domain\Event\EmailChangedEvent|\App\User\Domain\Event\PasswordChangedEvent)[]
     *
     * @psalm-return array{0?: \App\User\Domain\Event\EmailChangedEvent|\App\User\Domain\Event\PasswordChangedEvent}
     */
    #[\Override]
    public function update(
        UserUpdate $updateData,
        string $hashedNewPassword,
        string $eventID,
        EmailChangedEventFactoryInterface $emailChangedEventFactory,
        PasswordChangedEventFactoryInterface $passwordChangedEventFactory,
    ): array {
        $events = [];

        $events += $this->processNewEmail(
            $updateData->newEmail,
            $eventID,
            $emailChangedEventFactory
        );
        $events += $this->processNewPassword(
            $updateData->newPassword,
            $updateData->oldPassword,
            $eventID,
            $passwordChangedEventFactory
        );

        $this->initials = $updateData->newInitials;
        $this->password = $hashedNewPassword;

        return $events;
    }

    /** @psalm-suppress PossiblyUnusedMethod API Platform serialization */
    public function isConfirmed(): bool
    {
        return $this->confirmed;
    }

    /**
     * @internal For Doctrine ORM hydration and test fixtures only. Use confirm() for business logic.
     *
     * @psalm-suppress PossiblyUnusedMethod Doctrine hydration
     *
     * @see User::confirm()
     */
    public function setConfirmed(bool $confirmed): void
    {
        $this->confirmed = $confirmed;
    }

    /** @psalm-suppress PossiblyUnusedMethod API Platform serialization */
    public function isTwoFactorEnabled(): bool
    {
        return $this->twoFactorEnabled;
    }

    /** @psalm-suppress PossiblyUnusedMethod API Platform serialization */
    public function getTwoFactorSecret(): ?string
    {
        return $this->twoFactorSecret;
    }

    /**
     * @internal For Doctrine ORM hydration and test fixtures only.
     *
     * @psalm-suppress PossiblyUnusedMethod Doctrine hydration
     */
    public function setTwoFactorEnabled(bool $twoFactorEnabled): void
    {
        $this->twoFactorEnabled = $twoFactorEnabled;
    }

    /**
     * @internal For Doctrine ORM hydration and test fixtures only.
     *
     * @psalm-suppress PossiblyUnusedMethod Doctrine hydration
     */
    public function setTwoFactorSecret(?string $twoFactorSecret): void
    {
        $this->twoFactorSecret = $twoFactorSecret;
    }

    /**
     * @return \App\User\Domain\Event\EmailChangedEvent[]
     *
     * @psalm-return list{0?: \App\User\Domain\Event\EmailChangedEvent}
     */
    private function processNewEmail(
        string $newEmail,
        string $eventID,
        EmailChangedEventFactoryInterface $emailChangedEventFactory,
    ): array {
        $events = [];
        if ($newEmail !== $this->email) {
            $oldEmail = $this->email;
            $this->email = $newEmail;
            $this->confirmed = false;
            $events[] =
                $emailChangedEventFactory->create($this, $oldEmail, $eventID);
        }

        return $events;
    }

    /**
     * @return \App\User\Domain\Event\PasswordChangedEvent[]
     *
     * @psalm-return list{0?: \App\User\Domain\Event\PasswordChangedEvent}
     */
    private function processNewPassword(
        string $newPassword,
        string $oldPassword,
        string $eventID,
        PasswordChangedEventFactoryInterface $passwordChangedEventFactory
    ): array {
        $events = [];
        if ($newPassword !== $oldPassword) {
            $events[] =
                $passwordChangedEventFactory->create($this->email, $eventID);
        }
        return $events;
    }
}
