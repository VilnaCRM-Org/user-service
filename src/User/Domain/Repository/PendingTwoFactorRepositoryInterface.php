<?php

declare(strict_types=1);

namespace App\User\Domain\Repository;

use App\User\Domain\Entity\PendingTwoFactor;
use DateTimeImmutable;

interface PendingTwoFactorRepositoryInterface
{
    public function save(PendingTwoFactor $pendingTwoFactor): void;

    public function findById(string $id): ?PendingTwoFactor;

    public function consumeIfActive(string $id, DateTimeImmutable $currentTime): bool;

    public function delete(PendingTwoFactor $pendingTwoFactor): void;
}
