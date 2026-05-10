<?php

declare(strict_types=1);

namespace App\User\Domain\Factory;

use App\User\Domain\Entity\PendingTwoFactor;
use DateTimeImmutable;

interface PendingTwoFactorFactoryInterface
{
    public function create(
        string $id,
        string $userId,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $expiresAt = null,
    ): PendingTwoFactor;
}
