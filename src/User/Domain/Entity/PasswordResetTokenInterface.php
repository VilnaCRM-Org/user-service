<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

interface PasswordResetTokenInterface
{
    public function getTokenValue(): string;

    public function getUserID(): string;

    public function getExpiresAt(): \DateTimeImmutable;

    public function getCreatedAt(): \DateTimeImmutable;

    public function isUsed(): bool;

    public function isExpired(): bool;

    public function markAsUsed(): void;
}
