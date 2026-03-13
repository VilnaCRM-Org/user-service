<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Domain\Entity\User;

interface RecoveryCodeBatchFactoryInterface
{
    /**
     * @return list<string>
     */
    public function create(User $user): array;
}
