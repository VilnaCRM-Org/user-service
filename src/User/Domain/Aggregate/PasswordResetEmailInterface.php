<?php

declare(strict_types=1);

namespace App\User\Domain\Aggregate;

interface PasswordResetEmailInterface
{
    public function send(string $eventID): void;
}
