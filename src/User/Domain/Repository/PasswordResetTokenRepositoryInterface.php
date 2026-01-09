<?php

declare(strict_types=1);

namespace App\User\Domain\Repository;

use App\User\Domain\Entity\PasswordResetTokenInterface;

interface PasswordResetTokenRepositoryInterface
{
    public function save(
        PasswordResetTokenInterface $passwordResetToken
    ): void;

    public function findByToken(string $token): ?PasswordResetTokenInterface;

    public function findByUserID(
        string $userID
    ): ?PasswordResetTokenInterface;

    public function delete(
        PasswordResetTokenInterface $passwordResetToken
    ): void;

    public function deleteAll(): void;

    /**
     * @param array<PasswordResetTokenInterface> $tokens
     */
    public function saveBatch(array $tokens): void;
}
