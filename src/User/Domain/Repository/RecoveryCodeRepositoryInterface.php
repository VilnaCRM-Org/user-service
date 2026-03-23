<?php

declare(strict_types=1);

namespace App\User\Domain\Repository;

use App\User\Domain\Entity\RecoveryCode;
use DateTimeImmutable;

interface RecoveryCodeRepositoryInterface
{
    public function save(RecoveryCode $recoveryCode): void;

    public function findById(string $id): ?RecoveryCode;

    /**
     * @return array<RecoveryCode>
     */
    public function findByUserId(string $userId): array;

    public function markAsUsedIfUnused(string $id, DateTimeImmutable $usedAt): bool;

    public function delete(RecoveryCode $recoveryCode): void;

    public function deleteByUserId(string $userId): int;
}
