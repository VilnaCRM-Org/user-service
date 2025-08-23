<?php

declare(strict_types=1);

namespace App\User\Domain\Repository;

use App\User\Domain\Entity\PasswordResetToken;
use App\User\Domain\Entity\PasswordResetTokenInterface;

/**
 * @extends RepositoryInterface<PasswordResetToken>
 */
interface PasswordResetTokenRepositoryInterface extends RepositoryInterface
{
    public function save(PasswordResetTokenInterface $token): void;

    public function find(string $tokenValue): ?PasswordResetTokenInterface;

    public function findByUserId(string $userID): ?PasswordResetTokenInterface;

    public function delete(PasswordResetTokenInterface $token): void;
}