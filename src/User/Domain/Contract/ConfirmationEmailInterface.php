<?php

declare(strict_types=1);

namespace App\User\Domain\Contract;

interface ConfirmationEmailInterface
{
    public function send(string $eventID): void;
}
