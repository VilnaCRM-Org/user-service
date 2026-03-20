<?php

declare(strict_types=1);

namespace App\User\Application\Adapter;

interface PasswordHasherInterface
{
    public function hash(string $plainPassword): string;

    public function verify(string $hashedPassword, string $plainPassword): bool;

    public function needsRehash(string $hashedPassword): bool;
}
