<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Repository;

use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Exception\DuplicateEmailException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

final readonly class MariaDBUserRepository implements UserRepositoryInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save($user): void
    {
        try {
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException $e) {
            throw new DuplicateEmailException($user->getEmail());
        }
    }

    public function find($userID): ?User
    {
        return $this->entityManager->find(User::class, $userID);
    }

    public function delete($user): void
    {
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }
}
