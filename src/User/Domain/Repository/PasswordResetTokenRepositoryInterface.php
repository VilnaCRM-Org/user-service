<?php

declare(strict_types=1);

namespace App\User\Domain\Repository;

use App\User\Domain\Collection\PasswordResetTokenCollection;
use App\User\Domain\Entity\PasswordResetTokenInterface;

interface PasswordResetTokenRepositoryInterface
{
    public function save(
        PasswordResetTokenInterface $passwordResetToken
    ): void;

    public function findByToken(string $token): ?PasswordResetTokenInterface;

    public function deleteAll(): void;

    public function saveBatch(PasswordResetTokenCollection $tokens): void;
}
