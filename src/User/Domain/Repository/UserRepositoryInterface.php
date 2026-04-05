<?php

declare(strict_types=1);

namespace App\User\Domain\Repository;

use App\User\Domain\Collection\UserCollection;
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

    public function saveBatch(UserCollection $users): void;

    public function deleteBatch(UserCollection $users): void;

    public function deleteAll(): void;
}
