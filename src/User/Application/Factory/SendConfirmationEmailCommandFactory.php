<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\Command\SendConfirmationEmailCommand;
use App\User\Domain\Aggregate\ConfirmationEmailInterface;

final class SendConfirmationEmailCommandFactory implements
    SendConfirmationEmailCommandFactoryInterface
{
    #[\Override]
    public function create(
        ConfirmationEmailInterface $confirmationEmail
    ): SendConfirmationEmailCommand {
        return new SendConfirmationEmailCommand($confirmationEmail);
    }
}
