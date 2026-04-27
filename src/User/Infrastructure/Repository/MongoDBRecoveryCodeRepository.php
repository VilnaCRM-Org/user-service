<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Repository;

use App\User\Domain\Collection\RecoveryCodeCollection;
use App\User\Domain\Entity\RecoveryCode;
use App\User\Domain\Repository\RecoveryCodeRepositoryInterface;
use DateTimeImmutable;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;
use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * @extends ServiceDocumentRepository<RecoveryCode>
 */
final class MongoDBRecoveryCodeRepository extends ServiceDocumentRepository implements
    RecoveryCodeRepositoryInterface
{
    public function __construct(
        private readonly DocumentManager $documentManager,
        ManagerRegistry $registry,
        private readonly MongoDBWriteResultCounter $writeResultCounter,
    ) {
        parent::__construct($registry, RecoveryCode::class);
    }

    #[\Override]
    public function save(RecoveryCode $recoveryCode): void
    {
        $this->documentManager->persist($recoveryCode);
        $this->documentManager->flush();
    }

    #[\Override]
    public function saveAll(RecoveryCode ...$recoveryCodes): void
    {
        foreach ($recoveryCodes as $recoveryCode) {
            $this->documentManager->persist($recoveryCode);
        }

        $this->documentManager->flush();
    }

    #[\Override]
    public function findById(string $id): ?RecoveryCode
    {
        return $this->find($id);
    }

    #[\Override]
    public function findByUserId(string $userId): RecoveryCodeCollection
    {
        $result = $this->findBy(['userId' => $userId]);

        return new RecoveryCodeCollection(...array_values($result));
    }

    #[\Override]
    public function countUnusedByUserId(string $userId): int
    {
        $result = $this->createQueryBuilder()
            ->field('userId')->equals($userId)
            ->field('usedAt')->equals(null)
            ->count()
            ->getQuery()
            ->execute();

        if (!is_int($result)) {
            return 0;
        }

        return max(0, $result);
    }

    #[\Override]
    public function markAsUsedIfUnused(string $id, DateTimeImmutable $usedAt): bool
    {
        $result = $this->createQueryBuilder()
            ->updateOne()
            ->field('id')->equals($id)
            ->field('usedAt')->equals(null)
            ->field('usedAt')->set($usedAt)
            ->getQuery()
            ->execute();

        if (!$this->writeResultCounter->wasDocumentUpdated($result)) {
            return false;
        }

        $this->syncManagedRecoveryCodeUsage($id, $usedAt);

        return true;
    }

    #[\Override]
    public function delete(RecoveryCode $recoveryCode): void
    {
        $this->documentManager->remove($recoveryCode);
        $this->documentManager->flush();
    }

    /**
     * @psalm-return int<0, max>
     */
    #[\Override]
    public function deleteByUserId(string $userId): int
    {
        $result = $this->createQueryBuilder()
            ->remove()
            ->field('userId')->equals($userId)
            ->getQuery()
            ->execute();

        $deletedCount = $this->writeResultCounter->removedDocumentCount($result);
        if ($deletedCount > 0) {
            $this->documentManager->clear(RecoveryCode::class);
        }

        return $deletedCount;
    }

    private function syncManagedRecoveryCodeUsage(string $id, DateTimeImmutable $usedAt): void
    {
        $recoveryCode = $this->find($id);
        if (!$recoveryCode instanceof RecoveryCode) {
            return;
        }

        $recoveryCode->markAsUsed($usedAt);
    }
}
