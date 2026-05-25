<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use DateTimeImmutable;

final class PasskeyCredential
{
    private ?DateTimeImmutable $lastUsedAt = null;

    public function __construct(
        private string $id,
        private string $userId,
        private string $credentialId,
        private string $credentialRecord,
        private string $label,
        private DateTimeImmutable $createdAt
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getCredentialId(): string
    {
        return $this->credentialId;
    }

    public function getCredentialRecord(): string
    {
        return $this->credentialRecord;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getLastUsedAt(): ?DateTimeImmutable
    {
        return $this->lastUsedAt;
    }

    public function markUsed(string $credentialRecord, DateTimeImmutable $usedAt): void
    {
        $this->credentialRecord = $credentialRecord;
        $this->lastUsedAt = $usedAt;
    }
}
