<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

interface ConfirmationTokenInterface
{
    public function send(?\DateTimeImmutable $sendAt = null): void;
}
