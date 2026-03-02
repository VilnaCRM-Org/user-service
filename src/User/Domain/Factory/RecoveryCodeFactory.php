<?php

declare(strict_types=1);

namespace App\User\Domain\Factory;

use App\User\Domain\Entity\RecoveryCode;

final readonly class RecoveryCodeFactory implements RecoveryCodeFactoryInterface
{
    #[\Override]
    public function create(string $id, string $userId, string $plainCode): RecoveryCode
    {
        return new RecoveryCode($id, $userId, $plainCode);
    }
}
