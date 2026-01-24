<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Repository;

use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Logging\Middleware;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\NullLogger;

final class MariaDBUserRepository extends ServiceEntityRepository implements
    UserRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ManagerRegistry $registry,
        private readonly int $batchSize,
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

    public function findById(string $id): ?UserInterface
    {
        return $this->find($id);
    }

    /**
     * @param array<User> $users
     */
    public function saveBatch(array $users): void
    {
        $this->persistUsersInBatch($users);
    }

    /**
     * @codeCoverageIgnore Tested in integration tests
     *
     * @infection-ignore-all Tested in integration tests
     *
     * @param array<User> $users
     */
    public function deleteBatch(array $users): void
    {
        $this->removeUsersInBatch($users);
    }

    /**
     * @codeCoverageIgnore Tested in integration tests
     *
     * @infection-ignore-all Tested in integration tests
     */
    public function deleteAll(): void
    {
        $this->createQueryBuilder('u')
            ->delete()
            ->getQuery()
            ->execute();
    }

    /**
     * @param array<User> $users
     */
    private function persistUsersInBatch(array $users): void
    {
        $this->entityManager->getConnection()
            ->getConfiguration()
            ->setMiddlewares([new Middleware(new NullLogger())]);

        $usersForPersistence = array_values($users);

        array_walk(
            $usersForPersistence,
            function (User $user, int $index): void {
                $position = $index + 1;
                $this->entityManager->persist($user);

                if ($position % $this->batchSize === 0) {
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                }
            }
        );
        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    /**
     * @codeCoverageIgnore Tested in integration tests
     *
     * @infection-ignore-all Tested in integration tests
     *
     * @param array<User> $users
     */
    private function removeUsersInBatch(array $users): void
    {
        $this->entityManager->getConnection()
            ->getConfiguration()
            ->setMiddlewares([new Middleware(new NullLogger())]);

        $usersForRemoval = array_values($users);

        array_walk(
            $usersForRemoval,
            function (User $user, int $index): void {
                $position = $index + 1;
                $this->entityManager->remove($user);

                if ($position % $this->batchSize === 0) {
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                }
            }
        );
        $this->entityManager->flush();
        $this->entityManager->clear();
    }
}
