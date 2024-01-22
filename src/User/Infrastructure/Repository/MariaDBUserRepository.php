<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Repository;

use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Exception\DuplicateEmailException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

final class MariaDBUserRepository extends ServiceEntityRepository implements
    UserRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ManagerRegistry $registry
    ) {
        parent::__construct($this->registry, User::class);
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

    public function delete($user): void
    {
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    public function findByEmail($email): ?UserInterface
    {
        return $this->findOneBy(['email' => $email]);
    }
}
