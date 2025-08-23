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
    /**
     * @param PasswordResetToken $token
     */
    public function save(object $token): void;

    public function find(string $tokenValue): ?PasswordResetTokenInterface;

    public function findByUserId(string $userID): ?PasswordResetTokenInterface;

    /**
     * @param PasswordResetToken $token
     */
    public function delete(object $token): void;
}