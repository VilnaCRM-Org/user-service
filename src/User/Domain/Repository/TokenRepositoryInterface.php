<?php

declare(strict_types=1);

namespace App\User\Domain\Repository;

use App\User\Domain\Entity\ConfirmationToken;
use App\User\Domain\Entity\ConfirmationTokenInterface;

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
    public function find($tokenValue): ?ConfirmationTokenInterface;

    /**
     * @param string $userId
     */
    public function findByUserId($userId): ?ConfirmationTokenInterface;

    /**
     * @param ConfirmationToken $token
     */
    public function delete($token): void;
}
