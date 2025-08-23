<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

interface PasswordResetTokenInterface
{
    public function getTokenValue(): string;
    public function getUserID(): string;
    public function getCreatedAt(): \DateTimeImmutable;
    public function getExpiresAt(): \DateTimeImmutable;
    public function isExpired(?\DateTimeImmutable $now = null): bool;
}