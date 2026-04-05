<?php

declare(strict_types=1);

namespace App\User\Domain\Repository;

use App\User\Domain\Collection\AuthSessionCollection;
use App\User\Domain\Entity\AuthSession;

interface AuthSessionRepositoryInterface
{
    public function save(AuthSession $authSession): void;

    public function findById(string $id): ?AuthSession;

    public function findByUserId(string $userId): AuthSessionCollection;

    public function delete(AuthSession $authSession): void;
}
