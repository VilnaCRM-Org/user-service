<?php

declare(strict_types=1);

namespace App\User\Domain;

use App\User\Domain\Entity\User;

/**
 * @extends RepositoryInterface<User>
 */
interface UserRepositoryInterface extends RepositoryInterface
{
    /**
     * @param User $user
     */
    public function save($user): void;

    /**
     * @param string $userID
     */
    public function find($userID): ?User;

    /**
     * @param User $user
     */
    public function delete($user): void;
}
