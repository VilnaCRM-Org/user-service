<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\Command\SendConfirmationEmailCommand;
use App\User\Domain\Aggregate\ConfirmationEmailInterface;

interface SendConfirmationEmailCommandFactoryInterface
{
    public function create(
        ConfirmationEmailInterface $confirmationEmail
    ): SendConfirmationEmailCommand;
}
