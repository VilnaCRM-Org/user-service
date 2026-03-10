<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\Command\SendPasswordResetEmailCommand;
use App\User\Domain\Aggregate\PasswordResetEmailInterface;

interface SendPasswordResetEmailCommandFactoryInterface
{
    public function create(
        PasswordResetEmailInterface $passwordResetEmail
    ): SendPasswordResetEmailCommand;
}
