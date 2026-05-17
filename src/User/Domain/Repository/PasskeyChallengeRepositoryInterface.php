<?php

declare(strict_types=1);

namespace App\User\Domain\Repository;

use App\User\Domain\Entity\PasskeyChallenge;
use DateTimeImmutable;

interface PasskeyChallengeRepositoryInterface
{
    public function save(PasskeyChallenge $challenge): void;

    public function findById(string $id): ?PasskeyChallenge;

    public function claimActive(
        string $id,
        string $purpose,
        DateTimeImmutable $consumedAt
    ): ?PasskeyChallenge;

    public function delete(PasskeyChallenge $challenge): void;

    public function release(PasskeyChallenge $challenge): void;
}
