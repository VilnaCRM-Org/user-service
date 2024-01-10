<?php

declare(strict_types=1);

namespace App\User\Infrastructure\EventHandler;

use App\Shared\Domain\Bus\Command\CommandBus;
use App\Shared\Domain\Bus\Event\DomainEventSubscriber;
use App\User\Application\Command\SendConfirmationEmailCommand;
use App\User\Domain\Aggregate\ConfirmationEmail;
use App\User\Domain\Event\UserRegisteredEvent;
use App\User\Domain\Factory\ConfirmationTokenFactory;

class UserRegisteredEventHandler implements DomainEventSubscriber
{
    public function __construct(
        private CommandBus $commandBus,
        private ConfirmationTokenFactory $tokenFactory
    ) {
    }

    public static function subscribedTo(): array
    {
        return [UserRegisteredEvent::class];
    }

    public function __invoke(UserRegisteredEvent $event): void
    {
        $user = $event->user;
        $token = $this->tokenFactory->create($user->getId());

        $this->commandBus->dispatch(new SendConfirmationEmailCommand(new ConfirmationEmail($token, $user)));
    }
}
