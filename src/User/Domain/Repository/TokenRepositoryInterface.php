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
     * @param ConfirmationToken $entity
     */
    #[\Override]
    public function save(object $entity): void;

    #[\Override]
    public function find(string $id): ?ConfirmationTokenInterface;

    public function findByUserId(string $userID): ?ConfirmationTokenInterface;

    /**
     * @param ConfirmationToken $entity
     */
    #[\Override]
    public function delete(object $entity): void;
}
