<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Repository;

use App\User\Domain\Collection\UserCollection;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use InvalidArgumentException;

/**
 * @extends ServiceDocumentRepository<User>
 */
final class MongoDBUserRepository extends ServiceDocumentRepository implements
    UserRepositoryInterface
{
    public function __construct(
        private readonly DocumentManager $documentManager,
        ManagerRegistry $registry,
        private readonly int $batchSize,
    ) {
        if ($batchSize <= 0) {
            throw new InvalidArgumentException('Batch size must be greater than zero.');
        }
        parent::__construct($registry, User::class);
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

    /**
     * @return User|null
     */
    #[\Override]
    public function findByEmail(string $email): ?UserInterface
    {
        return $this->findOneBy(['email' => $email]);
    }

    /**
     * @return User|null
     */
    #[\Override]
    public function findById(string $id): ?UserInterface
    {
        return $this->find($id);
    }

    #[\Override]
    public function saveBatch(UserCollection $users): void
    {
        $this->persistUsersInBatch($users);
    }

    #[\Override]
    public function deleteBatch(UserCollection $users): void
    {
        $this->removeUsersInBatch($users);
    }

    #[\Override]
    public function deleteAll(): void
    {
        $this->createQueryBuilder()
            ->remove()
            ->getQuery()
            ->execute();
    }

    private function persistUsersInBatch(UserCollection $users): void
    {
        $usersForPersistence = $users->users;

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

    private function removeUsersInBatch(UserCollection $users): void
    {
        $usersForRemoval = $users->users;

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
