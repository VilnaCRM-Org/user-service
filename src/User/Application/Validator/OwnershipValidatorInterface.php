<?php

declare(strict_types=1);

namespace App\User\Application\Validator;

interface OwnershipValidatorInterface
{
    public function assertOwnership(string $resourceUserId): void;
}
