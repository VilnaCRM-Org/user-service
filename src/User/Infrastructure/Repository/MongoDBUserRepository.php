<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Repository;

use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;
use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * @extends ServiceDocumentRepository<User>
 *
 * @psalm-suppress UnusedClass - Used via dependency injection
 */
final class MongoDBUserRepository extends ServiceDocumentRepository implements
    UserRepositoryInterface
{
    public function __construct(
        private readonly DocumentManager $documentManager,
        private readonly ManagerRegistry $registry,
        private readonly int $batchSize,
    ) {
        parent::__construct($this->registry, User::class);
    }

    #[\Override]
    public function save(object $user): void
    {
        $this->documentManager->persist($user);
        $this->documentManager->flush();
    }

    #[\Override]
    public function delete(object $user): void
    {
        $this->documentManager->remove($user);
        $this->documentManager->flush();
    }

    #[\Override]
    public function findByEmail(string $email): ?UserInterface
    {
        return $this->findOneBy(['email' => $email]);
    }

    #[\Override]
    public function findById(string $id): ?UserInterface
    {
        return $this->find($id);
    }

    /**
     * @param array<User> $users
     */
    #[\Override]
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
    #[\Override]
    public function deleteBatch(array $users): void
    {
        $this->removeUsersInBatch($users);
    }

    /**
     * @codeCoverageIgnore Tested in integration tests
     *
     * @infection-ignore-all Tested in integration tests
     */
    #[\Override]
    public function deleteAll(): void
    {
        $this->createQueryBuilder()
            ->remove()
            ->getQuery()
            ->execute();
    }

    /**
     * @param array<User> $users
     */
    private function persistUsersInBatch(array $users): void
    {
        $usersForPersistence = array_values($users);

        array_walk(
            $usersForPersistence,
            function (User $user, int $index): void {
                $position = $index + 1;
                $this->documentManager->persist($user);

                if ($position % $this->batchSize === 0) {
                    $this->documentManager->flush();
                    $this->documentManager->clear();
                }
            }
        );
        $this->documentManager->flush();
        $this->documentManager->clear();
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
        $usersForRemoval = array_values($users);

        array_walk(
            $usersForRemoval,
            function (User $user, int $index): void {
                $position = $index + 1;
                $this->documentManager->remove($user);

                if ($position % $this->batchSize === 0) {
                    $this->documentManager->flush();
                    $this->documentManager->clear();
                }
            }
        );
        $this->documentManager->flush();
        $this->documentManager->clear();
    }
}
