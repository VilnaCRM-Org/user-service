<?php

declare(strict_types=1);

namespace App\User\Domain\Factory;

use App\User\Domain\Entity\RecoveryCode;

interface RecoveryCodeFactoryInterface
{
    public function create(string $id, string $userId, string $plainCode): RecoveryCode;
}
