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
    public function markAsUsedIfUnused(string $id, DateTimeImmutable $usedAt): bool
    {
        $result = $this->createQueryBuilder()
            ->updateOne()
            ->field('id')->equals($id)
            ->field('usedAt')->equals(null)
            ->field('usedAt')->set($usedAt)
            ->getQuery()
            ->execute();

        if (!$this->wasDocumentUpdated($result)) {
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
        $codes = $this->findByUserId($userId);
        foreach ($codes as $code) {
            $this->documentManager->remove($code);
        }
        $this->documentManager->flush();

        return count($codes);
    }

    private function wasDocumentUpdated(mixed $result): bool
    {
        if (is_int($result)) {
            return $result > 0;
        }

        if (!is_object($result) || !method_exists($result, 'getModifiedCount')) {
            return false;
        }

        $modifiedCount = $result->getModifiedCount();

        return is_int($modifiedCount) && $modifiedCount > 0;
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
