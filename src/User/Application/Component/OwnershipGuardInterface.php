<?php

declare(strict_types=1);

namespace App\User\Application\Component;

interface OwnershipGuardInterface
{
    public function assertOwnership(string $resourceUserId): void;
}
