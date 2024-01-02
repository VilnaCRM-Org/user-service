<?php

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

        $this->eventBus->publish($confirmationEmail->send());
    }
}
