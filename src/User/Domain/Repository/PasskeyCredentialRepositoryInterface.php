<?php

declare(strict_types=1);

namespace App\User\Domain\Repository;

use App\User\Domain\Collection\PasskeyCredentialCollection;
use App\User\Domain\Entity\PasskeyCredential;

interface PasskeyCredentialRepositoryInterface
{
    public function save(PasskeyCredential $credential): void;

    public function delete(PasskeyCredential $credential): void;

    public function findByCredentialId(string $credentialId): ?PasskeyCredential;

    public function findByUserId(string $userId): PasskeyCredentialCollection;

    public function existsByCredentialId(string $credentialId): bool;
}
