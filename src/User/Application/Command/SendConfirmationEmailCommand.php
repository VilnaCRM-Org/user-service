<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\Shared\Domain\Bus\Command\CommandInterface;
use App\User\Domain\Aggregate\ConfirmationEmailInterface;

final readonly class SendConfirmationEmailCommand implements CommandInterface
{
    public function __construct(
        public ConfirmationEmailInterface $confirmationEmail
    ) {
    }
}
