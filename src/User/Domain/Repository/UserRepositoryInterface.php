<?php

declare(strict_types=1);

namespace App\User\Domain\Repository;

use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserInterface;

interface UserRepositoryInterface
{
    /**
     * @param User $user
     */
    public function save(object $user): void;

    public function findByEmail(string $email): ?UserInterface;

    public function findById(string $id): ?UserInterface;

    /**
     * @param User $user
     */
    public function delete(object $user): void;

    /**
     * @param array<User> $users
     */
    public function saveBatch(array $users): void;

    /**
     * @param array<User> $users
     */
    public function deleteBatch(array $users): void;

    /**
     * @param string $id
     *
     * @return User
     */
    public function find(
        mixed $id,
        ?int $lockMode = null,
        ?int $lockVersion = null
    ): ?object;

    public function deleteAll(): void;
}
