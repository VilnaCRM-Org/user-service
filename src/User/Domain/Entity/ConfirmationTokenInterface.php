<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

interface ConfirmationTokenInterface
{
    public function incrementTimesSent(): void;

    public function send(): void;
}
