<?php

declare(strict_types=1);

namespace App\User\Infrastructure;

use App\User\Domain\Entity\User;
use App\User\Domain\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class MariaDBUserRepository implements UserRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(User $user): void
    {
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}