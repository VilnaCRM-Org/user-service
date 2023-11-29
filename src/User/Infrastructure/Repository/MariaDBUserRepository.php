<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Repository;

use App\Shared\Domain\Bus\Event\EventBus;
use App\User\Domain\Entity\User\User;
use App\User\Domain\UserRepository;
use App\User\Infrastructure\Exceptions\UserNotFoundError;
use Doctrine\ORM\EntityManagerInterface;

final readonly class MariaDBUserRepository implements UserRepository
{
    public function __construct(private EntityManagerInterface $entityManager, private EventBus $eventBus)
    {
    }

    public function save(User $user): void
    {
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // $this->eventBus->publish(new UserRegisteredEvent());
    }

    public function find(string $userID): User
    {
        $user = $this->entityManager->find(User::class, $userID);

        if (!$user) {
            throw new UserNotFoundError();
        }

        return $user;
    }
}
