<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use DateTimeImmutable;

final class RecoveryCode
{
    private ?DateTimeImmutable $usedAt = null;

    private string $codeHash;

    public function __construct(
        private string $id,
        private string $userId,
        string $plainCode
    ) {
        $this->codeHash = $this->hash($plainCode);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getCodeHash(): string
    {
        return $this->codeHash;
    }

    public function getUsedAt(): ?DateTimeImmutable
    {
        return $this->usedAt;
    }

    public function markAsUsed(?DateTimeImmutable $usedAt = null): void
    {
        $this->usedAt = $usedAt ?? new DateTimeImmutable();
    }

    public function isUsed(): bool
    {
        return $this->usedAt !== null;
    }

    public function matchesCode(string $plainCode): bool
    {
        return hash_equals($this->codeHash, $this->hash($plainCode));
    }

    private function hash(string $value): string
    {
        return hash('sha256', $value);
    }
}
