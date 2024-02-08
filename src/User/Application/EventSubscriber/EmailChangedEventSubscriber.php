<?php

declare(strict_types=1);

namespace App\User\Application\EventSubscriber;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use App\User\Application\Factory\SendConfirmationEmailCommandFactoryInterface;
use App\User\Domain\Event\EmailChangedEvent;
use App\User\Domain\Factory\ConfirmationEmailFactoryInterface;
use App\User\Domain\Factory\ConfirmationTokenFactoryInterface;

final class EmailChangedEventSubscriber implements
    DomainEventSubscriberInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private ConfirmationTokenFactoryInterface $tokenFactory,
        private ConfirmationEmailFactoryInterface $confirmationEmailFactory,
        private SendConfirmationEmailCommandFactoryInterface $emailCmdFactory
    ) {
    }

    public function __invoke(EmailChangedEvent $event): void
    {
        $user = $event->user;
        $token = $this->tokenFactory->create($user->getId());

        $this->commandBus->dispatch(
            $this->emailCmdFactory->create(
                $this->confirmationEmailFactory->create($token, $user)
            )
        );
    }

    /**
     * @return array<DomainEvent>
     */
    public static function subscribedTo(): array
    {
        return [EmailChangedEvent::class];
    }
}
