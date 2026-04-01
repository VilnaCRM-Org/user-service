<?php

declare(strict_types=1);

namespace App\OAuth\Application\DTO;

use App\User\Domain\Entity\User;

final readonly class OAuthResolvedUser
{
    public function __construct(
        public User $user,
        public bool $newlyCreated,
    ) {
    }
}
