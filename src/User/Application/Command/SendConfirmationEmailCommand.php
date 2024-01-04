<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\Shared\Domain\Bus\Command\Command;
use App\User\Domain\Aggregate\ConfirmationEmail;

class SendConfirmationEmailCommand implements Command
{
    public function __construct(public readonly ConfirmationEmail $confirmationEmail)
    {
    }
}
