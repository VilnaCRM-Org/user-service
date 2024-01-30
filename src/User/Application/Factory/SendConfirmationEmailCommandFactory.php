<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\Command\SendConfirmationEmailCommand;
use App\User\Domain\Aggregate\ConfirmationEmail;

final class SendConfirmationEmailCommandFactory implements
    SendConfirmationEmailCommandFactoryInterface
{
    public function create(
        ConfirmationEmail $confirmationEmail
    ): SendConfirmationEmailCommand {
        return new SendConfirmationEmailCommand($confirmationEmail);
    }
}
