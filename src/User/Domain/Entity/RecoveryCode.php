<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use DateTimeImmutable;

final class RecoveryCode
{
    public const COUNT = 8;
    public const SEGMENT_LENGTH = 4;
    private const LEGACY_HASH_ALGORITHM = 'sha256';

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
        $normalizedCode = $this->normalizeCode($plainCode);
        if ($this->isPasswordHash($this->codeHash)) {
            return password_verify($normalizedCode, $this->codeHash);
        }

        return hash_equals($this->codeHash, $this->legacyHash($normalizedCode));
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public static function isValidFormat(string $code): bool
    {
        $segment = '[A-Za-z0-9]{' . self::SEGMENT_LENGTH . '}';
        return preg_match('/^' . $segment . '-' . $segment . '$/', $code) === 1;
    }

    private function hash(string $value): string
    {
        return password_hash($this->normalizeCode($value), PASSWORD_ARGON2ID);
    }

    private function legacyHash(string $value): string
    {
        return hash(self::LEGACY_HASH_ALGORITHM, $value);
    }

    private function normalizeCode(string $value): string
    {
        return strtolower($value);
    }

    private function isPasswordHash(string $value): bool
    {
        $info = password_get_info($value);

        return (string) ($info['algoName'] ?? 'unknown') !== 'unknown';
    }
}
