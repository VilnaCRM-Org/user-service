<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\User\Application\Command\SendConfirmationEmailCommand;

final class SendConfirmationEmailCommandHandler implements CommandHandlerInterface
{
    public function __construct(private EventBusInterface $eventBus)
    {
    }

    public function __invoke(SendConfirmationEmailCommand $command): void
    {
        $confirmationEmail = $command->confirmationEmail;

        $confirmationEmail->send();

        $this->eventBus->publish(...$confirmationEmail->pullDomainEvents());
    }
}
