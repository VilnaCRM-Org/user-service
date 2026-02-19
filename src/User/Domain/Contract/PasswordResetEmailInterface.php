<?php

declare(strict_types=1);

namespace App\User\Domain\Contract;

interface PasswordResetEmailInterface
{
    public function send(string $eventID): void;
}
