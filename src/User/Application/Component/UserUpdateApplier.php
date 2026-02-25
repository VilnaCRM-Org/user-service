<?php

declare(strict_types=1);

namespace App\User\Application\Component;

use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Factory\Event\EmailChangedEventFactoryInterface;
use App\User\Domain\Factory\Event\PasswordChangedEventFactoryInterface;
use App\User\Domain\Factory\Event\UserUpdatedEventFactoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserUpdate;

final readonly class UserUpdateApplier implements UserUpdateApplierInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private EmailChangedEventFactoryInterface $emailChangedEventFactory,
        private PasswordChangedEventFactoryInterface $passwordChangedFactory,
        private UserUpdatedEventFactoryInterface $userUpdatedEventFactory,
    ) {
    }

    #[\Override]
    /**
     * @return array<int, DomainEvent>
     */
    public function apply(
        UserInterface $user,
        UserUpdate $updateData,
        string $hashedPassword,
        string $eventId
    ): array {
        $previousEmail = $user->getEmail();

        $events = $user->update(
            $updateData,
            $hashedPassword,
            $eventId,
            $this->emailChangedEventFactory,
            $this->passwordChangedFactory
        );

        $this->userRepository->save($user);

        $events[] = $this->userUpdatedEventFactory->create(
            $user,
            $previousEmail !== $user->getEmail() ? $previousEmail : null,
            $eventId
        );

        return $events;
    }
}
