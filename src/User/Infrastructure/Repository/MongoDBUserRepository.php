<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Repository;

use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;
use Doctrine\ODM\MongoDB\DocumentManager;

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

    public function save(object $user): void
    {
        $this->documentManager->persist($user);
        $this->documentManager->flush();
    }

    public function delete(object $user): void
    {
        $this->documentManager->remove($user);
        $this->documentManager->flush();
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
     */
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
}
