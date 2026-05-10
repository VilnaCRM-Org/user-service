<?php

declare(strict_types=1);

namespace App\User\Domain\Repository;

use App\User\Domain\Entity\PasskeyCredential;

interface PasskeyCredentialRepositoryInterface
{
    public function save(PasskeyCredential $credential): void;

    public function findByCredentialId(string $credentialId): ?PasskeyCredential;

    /**
     * @return list<PasskeyCredential>
     */
    public function findByUserId(string $userId): array;

    public function existsByCredentialId(string $credentialId): bool;
}
