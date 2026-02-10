<?php

declare(strict_types=1);

namespace App\User\Domain\Repository;

use App\User\Domain\Entity\AuthSession;

interface AuthSessionRepositoryInterface
{
    public function save(AuthSession $authSession): void;

    public function findById(string $id): ?AuthSession;

    public function delete(AuthSession $authSession): void;
}
