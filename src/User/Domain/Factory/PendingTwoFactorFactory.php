<?php

declare(strict_types=1);

namespace App\User\Domain\Factory;

use App\User\Domain\Entity\PendingTwoFactor;
use DateTimeImmutable;

final readonly class PendingTwoFactorFactory implements PendingTwoFactorFactoryInterface
{
    #[\Override]
    public function create(
        string $id,
        string $userId,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $expiresAt = null,
    ): PendingTwoFactor {
        return new PendingTwoFactor($id, $userId, $createdAt, $expiresAt);
    }
}
