<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Publisher;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Factory\Event\PasswordResetConfirmedEventFactoryInterface;
use App\User\Domain\Factory\Event\UserUpdatedEventFactoryInterface;
use Symfony\Component\Uid\Factory\UuidFactory;

final readonly class PasswordResetConfirmationPublisher
{
    public function __construct(
        private EventBusInterface $eventBus,
        private UuidFactory $uuidFactory,
        private PasswordResetConfirmedEventFactoryInterface $eventFactory,
        private UserUpdatedEventFactoryInterface $userUpdatedEventFactory
    ) {
    }

    public function publish(UserInterface $user): void
    {
        $eventId = (string) $this->uuidFactory->create();

        $this->eventBus->publish(
            $this->eventFactory->create($user->getId(), $eventId),
            $this->userUpdatedEventFactory->create($user, null, $eventId)
        );
    }
}
