<?php

declare(strict_types=1);

namespace App\User\Infrastructure\EventSubscriber;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use App\User\Application\Command\SendConfirmationEmailCommand;
use App\User\Domain\Event\UserRegisteredEvent;
use App\User\Domain\Factory\ConfirmationEmailFactory;
use App\User\Domain\Factory\ConfirmationTokenFactory;

final class UserRegisteredEventSubscriber implements
    DomainEventSubscriberInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private ConfirmationTokenFactory $tokenFactory,
        private ConfirmationEmailFactory $confirmationEmailFactory
    ) {
    }

    public function __invoke(UserRegisteredEvent $event): void
    {
        $user = $event->user;
        $token = $this->tokenFactory->create($user->getId());

        $this->commandBus->dispatch(
            new SendConfirmationEmailCommand(
                $this->confirmationEmailFactory->create($token, $user)
            )
        );
    }

    /**
     * @return array<DomainEvent>
     */
    public static function subscribedTo(): array
    {
        return [UserRegisteredEvent::class];
    }
}
