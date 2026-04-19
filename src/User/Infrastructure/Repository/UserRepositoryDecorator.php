<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Repository;

use App\User\Domain\Collection\UserCollection;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Repository\UserRepositoryInterface;

abstract class UserRepositoryDecorator implements UserRepositoryInterface
{
    public function __construct(
        protected readonly UserRepositoryInterface $inner
    ) {
    }

    #[\Override]
    public function save(object $user): void
    {
        $this->inner->save($user);
    }

    #[\Override]
    public function delete(object $user): void
    {
        $this->inner->delete($user);
    }

    #[\Override]
    public function findByEmail(string $email): ?UserInterface
    {
        return $this->inner->findByEmail($email);
    }

    /**
     * @param array<int, string> $emails
     */
    #[\Override]
    public function findByEmails(array $emails): UserCollection
    {
        return $this->inner->findByEmails($emails);
    }

    #[\Override]
    public function findById(string $id): ?UserInterface
    {
        return $this->inner->findById($id);
    }

    #[\Override]
    public function saveBatch(UserCollection $users): void
    {
        $this->inner->saveBatch($users);
    }

    #[\Override]
    public function deleteBatch(UserCollection $users): void
    {
        $this->inner->deleteBatch($users);
    }

    #[\Override]
    public function deleteAll(): void
    {
        $this->inner->deleteAll();
    }
}
