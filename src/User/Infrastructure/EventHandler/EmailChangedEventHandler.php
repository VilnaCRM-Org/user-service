<?php

declare(strict_types=1);

namespace App\User\Infrastructure\EventHandler;

use App\Shared\Domain\Bus\Command\CommandBus;
use App\Shared\Domain\Bus\Event\DomainEventSubscriber;
use App\User\Application\Command\SendConfirmationEmailCommand;
use App\User\Domain\Aggregate\ConfirmationEmail;
use App\User\Domain\Factory\ConfirmationTokenFactory;
use App\User\Infrastructure\Event\EmailChangedEvent;

class EmailChangedEventHandler implements DomainEventSubscriber
{
    public function __construct(private CommandBus $commandBus, private ConfirmationTokenFactory $tokenFactory)
    {
    }

    public function __invoke(EmailChangedEvent $event): void
    {
        $user = $event->user;
        $token = $this->tokenFactory->create($user->getId());

        $this->commandBus->dispatch(new SendConfirmationEmailCommand(new ConfirmationEmail($token, $user)));
    }

    public static function subscribedTo(): array
    {
        return [EmailChangedEvent::class];
    }
}
