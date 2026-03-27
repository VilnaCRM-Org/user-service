<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use App\Shared\Domain\ValueObject\UuidInterface;
use App\User\Domain\Event\UserConfirmedEvent;
use App\User\Domain\Factory\Event\UserConfirmedEventFactoryInterface;
use App\User\Domain\Factory\Event\UserUpdateEventFactoryInterface;
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
     * @psalm-api
     *
     * @internal For Doctrine ORM hydration and test fixtures only.
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
     * @psalm-api
     *
     * @internal For Doctrine ORM hydration and test fixtures only. Use update() for business logic.
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
     * @psalm-api
     *
     * @internal For Doctrine ORM hydration and test fixtures only. Use update() for business logic.
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
    #[\Override]
    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    #[\Override]
    public function upgradePasswordHash(string $newHash): void
    {
        $this->password = $newHash;
    }

    #[\Override]
    public function enableTwoFactor(): void
    {
        $this->twoFactorEnabled = true;
    }

    #[\Override]
    public function disableTwoFactor(): void
    {
        $this->twoFactorEnabled = false;
        $this->twoFactorSecret = null;
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
     * @return array<\App\User\Domain\Event\EmailChangedEvent|\App\User\Domain\Event\PasswordChangedEvent>
     *
     * @psalm-return array{0?: \App\User\Domain\Event\EmailChangedEvent|\App\User\Domain\Event\PasswordChangedEvent}
     */
    #[\Override]
    public function update(
        UserUpdate $updateData,
        string $hashedNewPassword,
        string $eventID,
        UserUpdateEventFactoryInterface $userUpdateEventFactory,
    ): array {
        $events = [];

        $events += $this->processNewEmail(
            $updateData->newEmail,
            $eventID,
            $userUpdateEventFactory
        );
        $events += $this->processNewPassword(
            $updateData->newPassword,
            $updateData->oldPassword,
            $eventID,
            $userUpdateEventFactory
        );

        $this->initials = $updateData->newInitials;
        $this->password = $hashedNewPassword;

        return $events;
    }

    public function isConfirmed(): bool
    {
        return $this->confirmed;
    }

    /**
     * @internal For Doctrine ORM hydration and test fixtures only.
     *
     * @psalm-api
     *
     * @see User::confirm()
     */
    public function setConfirmed(bool $confirmed): void
    {
        $this->confirmed = $confirmed;
    }

    public function isTwoFactorEnabled(): bool
    {
        return $this->twoFactorEnabled;
    }

    public function getTwoFactorSecret(): ?string
    {
        return $this->twoFactorSecret;
    }

    /**
     * @internal For Doctrine ORM hydration and test fixtures only.
     */
    public function setTwoFactorEnabled(bool $twoFactorEnabled): void
    {
        $this->twoFactorEnabled = $twoFactorEnabled;
    }

    /**
     * @internal For Doctrine ORM hydration and test fixtures only.
     */
    public function setTwoFactorSecret(?string $twoFactorSecret): void
    {
        $this->twoFactorSecret = $twoFactorSecret;
    }

    /**
     * @return array<\App\User\Domain\Event\EmailChangedEvent>
     *
     * @psalm-return list{0?: \App\User\Domain\Event\EmailChangedEvent}
     */
    private function processNewEmail(
        string $newEmail,
        string $eventID,
        UserUpdateEventFactoryInterface $userUpdateEventFactory,
    ): array {
        $events = [];
        if ($newEmail !== $this->email) {
            $oldEmail = $this->email;
            $this->email = $newEmail;
            $this->confirmed = false;
            $events[] = $userUpdateEventFactory->createEmailChanged(
                $this,
                $oldEmail,
                $eventID
            );
        }

        return $events;
    }

    /**
     * @return array<\App\User\Domain\Event\PasswordChangedEvent>
     *
     * @psalm-return list{0?: \App\User\Domain\Event\PasswordChangedEvent}
     */
    private function processNewPassword(
        string $newPassword,
        string $oldPassword,
        string $eventID,
        UserUpdateEventFactoryInterface $userUpdateEventFactory
    ): array {
        $events = [];
        if ($newPassword !== $oldPassword) {
            $events[] = $userUpdateEventFactory->createPasswordChanged(
                $this->email,
                $eventID
            );
        }
        return $events;
    }
}
