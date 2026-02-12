<?php

declare(strict_types=1);

namespace App\User\Domain\Repository;

use App\User\Domain\Entity\RecoveryCode;

interface RecoveryCodeRepositoryInterface
{
    public function save(RecoveryCode $recoveryCode): void;

    public function findById(string $id): ?RecoveryCode;

    /**
     * @return array<RecoveryCode>
     */
    public function findByUserId(string $userId): array;

    public function delete(RecoveryCode $recoveryCode): void;

    public function deleteByUserId(string $userId): int;
}
