<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use DateTimeImmutable;

final class RecoveryCode
{
    public const COUNT = 8;
    public const SEGMENT_LENGTH = 4;
    private const HASH_MEMORY_COST = 19456;
    private const HASH_TIME_COST = PASSWORD_ARGON2_DEFAULT_TIME_COST;
    private const HASH_THREADS = 1;

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
        return password_verify($this->normalizeCode($plainCode), $this->codeHash);
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public static function isValidFormat(string $code): bool
    {
        $segment = '[A-Za-z0-9]{' . self::SEGMENT_LENGTH . '}';
        return preg_match('/^' . $segment . '-' . $segment . '$/', $code) === 1;
    }

    private function hash(string $value): string
    {
        return password_hash($this->normalizeCode($value), PASSWORD_ARGON2ID, [
            'memory_cost' => self::HASH_MEMORY_COST,
            'time_cost' => self::HASH_TIME_COST,
            'threads' => self::HASH_THREADS,
        ]);
    }

    private function normalizeCode(string $value): string
    {
        return strtolower($value);
    }
}
