<?php

declare(strict_types=1);

namespace App\User\Application\Service;

use App\User\Domain\Entity\User;

interface RecoveryCodeGeneratorInterface
{
    /**
     * Generates, stores, and returns plain-text recovery codes for the user.
     *
     * @return list<string>
     */
    public function generateAndStore(User $user): array;
}
