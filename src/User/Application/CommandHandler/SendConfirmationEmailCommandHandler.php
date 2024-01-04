<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandler;
use App\Shared\Domain\Bus\Event\EventBus;
use App\User\Application\Command\SendConfirmationEmailCommand;

class SendConfirmationEmailCommandHandler implements CommandHandler
{
    public function __construct(private EventBus $eventBus)
    {
    }

    public function __invoke(SendConfirmationEmailCommand $command)
    {
        $confirmationEmail = $command->confirmationEmail;

        $confirmationEmail->send();

        $this->eventBus->publish(...$confirmationEmail->pullDomainEvents());
    }
}
