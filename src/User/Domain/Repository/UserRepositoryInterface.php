<?php

declare(strict_types=1);

namespace App\User\Domain\Repository;

use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserInterface;

/**
 * @extends RepositoryInterface<User>
 */
interface UserRepositoryInterface
{
    /**
     * @param User $user
     */
    public function save($user): void;

    /**
     * @param string $email
     */
    public function findByEmail($email): ?UserInterface;

    /**
     * @param User $user
     */
    public function delete($user): void;
}
