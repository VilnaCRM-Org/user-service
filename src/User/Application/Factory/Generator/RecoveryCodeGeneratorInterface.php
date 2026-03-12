<?php

declare(strict_types=1);

namespace App\User\Application\Factory\Generator;

use App\User\Domain\Entity\User;

interface RecoveryCodeGeneratorInterface
{
    /**
     * @return list<string>
     */
    public function generateAndStore(User $user): array;
}
