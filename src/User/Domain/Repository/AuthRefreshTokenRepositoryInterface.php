<?php

declare(strict_types=1);

namespace App\User\Domain\Repository;

use App\User\Domain\Entity\AuthRefreshToken;

interface AuthRefreshTokenRepositoryInterface
{
    public function save(AuthRefreshToken $authRefreshToken): void;

    public function findById(string $id): ?AuthRefreshToken;

    public function findByTokenHash(string $tokenHash): ?AuthRefreshToken;

    public function delete(AuthRefreshToken $authRefreshToken): void;
}
