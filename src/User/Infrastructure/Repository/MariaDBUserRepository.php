<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Repository;

use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

final class MariaDBUserRepository extends ServiceEntityRepository implements
    UserRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ManagerRegistry $registry
    ) {
        parent::__construct($this->registry, User::class);
    }

    public function save(object $user): void
    {
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    public function delete(object $user): void
    {
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    public function findByEmail(string $email): ?UserInterface
    {
        return $this->findOneBy(['email' => $email]);
    }
}
