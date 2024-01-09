<?php

declare(strict_types=1);

namespace App\User\Domain;

use App\User\Domain\Entity\ConfirmationToken;

interface TokenRepositoryInterface
{
    public function save(ConfirmationToken $token): void;

    public function findByTokenValue(string $tokenValue): ?ConfirmationToken;

    public function findByUserId(string $userId): ?ConfirmationToken;

    public function delete(ConfirmationToken $token): void;
}
