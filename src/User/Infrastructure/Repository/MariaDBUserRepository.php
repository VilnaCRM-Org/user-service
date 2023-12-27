<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Repository;

use App\User\Domain\Entity\User\User;
use App\User\Domain\UserRepositoryInterface;
use App\User\Infrastructure\Exception\DuplicateEmailException;
use App\User\Infrastructure\Exception\UserNotFoundException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

final readonly class MariaDBUserRepository implements UserRepositoryInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(User $user): void
    {
        try {
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException $e) {
            throw new DuplicateEmailException($user->getEmail());
        }
    }

    public function find(string $userID): User
    {
        $user = $this->entityManager->find(User::class, $userID);

        if (!$user) {
            throw new UserNotFoundException();
        }

        return $user;
    }
}
