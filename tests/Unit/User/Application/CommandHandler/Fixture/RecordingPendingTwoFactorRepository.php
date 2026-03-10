<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler\Fixture;

use App\User\Domain\Entity\PendingTwoFactor;
use App\User\Domain\Repository\PendingTwoFactorRepositoryInterface;
use DateTimeImmutable;

final class RecordingPendingTwoFactorRepository implements PendingTwoFactorRepositoryInterface
{
    private ?PendingTwoFactor $saved = null;

    #[\Override]
    public function save(PendingTwoFactor $pendingTwoFactor): void
    {
        $this->saved = $pendingTwoFactor;
    }

    #[\Override]
    public function findById(string $id): ?PendingTwoFactor
    {
        return null;
    }

    #[\Override]
    public function consumeIfActive(string $id, DateTimeImmutable $currentTime): bool
    {
        return false;
    }

    #[\Override]
    public function delete(PendingTwoFactor $pendingTwoFactor): void
    {
    }

    public function saved(): ?PendingTwoFactor
    {
        return $this->saved;
    }
}
