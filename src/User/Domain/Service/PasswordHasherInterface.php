<?php

declare(strict_types=1);

namespace App\User\Domain\Service;

interface PasswordHasherInterface
{
    public function hash(string $plainPassword): string;
}
