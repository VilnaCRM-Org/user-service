<?php

declare(strict_types=1);

namespace App\User\Application\EventSubscriber;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use App\User\Application\Factory\SendConfirmationEmailCommandFactoryInterface;
use App\User\Domain\Event\UserRegisteredEvent;
use App\User\Domain\Factory\ConfirmationEmailFactoryInterface;
use App\User\Domain\Factory\ConfirmationTokenFactoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;

final readonly class UserRegisteredEventSubscriber implements
    DomainEventSubscriberInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private ConfirmationTokenFactoryInterface $tokenFactory,
        private ConfirmationEmailFactoryInterface $confirmationEmailFactory,
        private SendConfirmationEmailCommandFactoryInterface $emailCmdFactory,
        private UserRepositoryInterface $userRepository
    ) {
    }

    public function __invoke(UserRegisteredEvent $event): void
    {
        $user = $this->userRepository->findById($event->userId);
        $token = $this->tokenFactory->create($event->userId);

        $this->commandBus->dispatch(
            $this->emailCmdFactory->create(
                $this->confirmationEmailFactory->create($token, $user)
            )
        );
    }

    /**
     * @return array<string>
     *
     * @psalm-return list{UserRegisteredEvent::class}
     */
    #[\Override]
    public function subscribedTo(): array
    {
        return [UserRegisteredEvent::class];
    }
}
