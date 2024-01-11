<?php

declare(strict_types=1);

namespace App\User\Domain\Repository;

use App\User\Domain\Entity\ConfirmationToken;

/**
 * @extends RepositoryInterface<ConfirmationToken>
 */
interface TokenRepositoryInterface extends RepositoryInterface
{
    /**
     * @param ConfirmationToken $token
     */
    public function save($token): void;

    /**
     * @param string $tokenValue
     */
    public function find($tokenValue): ?ConfirmationToken;

    /**
     * @param string $userId
     */
    public function findByUserId($userId): ?ConfirmationToken;

    /**
     * @param ConfirmationToken $token
     */
    public function delete($token): void;
}
