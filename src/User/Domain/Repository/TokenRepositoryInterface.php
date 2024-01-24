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
    public function save(object $token): void;

    /**
     * @param string $tokenValue
     */
    public function find(string $tokenValue): ?ConfirmationTokenInterface;

    /**
     * @param string $userID
     */
    public function findByUserId($userID): ?ConfirmationTokenInterface;

    /**
     * @param ConfirmationToken $token
     */
    public function delete(object $token): void;
}
