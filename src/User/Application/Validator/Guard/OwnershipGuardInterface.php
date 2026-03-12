<?php

declare(strict_types=1);

namespace App\User\Application\Validator\Guard;

interface OwnershipGuardInterface
{
    public function assertOwnership(string $resourceUserId): void;
}
