<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Repository;

use App\User\Domain\Entity\PendingTwoFactor;
use App\User\Domain\Repository\PendingTwoFactorRepositoryInterface;
use DateTimeImmutable;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;
use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * @extends ServiceDocumentRepository<PendingTwoFactor>
 */
final class MongoDBPendingTwoFactorRepository extends ServiceDocumentRepository implements
    PendingTwoFactorRepositoryInterface
{
    public function __construct(
        private readonly DocumentManager $documentManager,
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, PendingTwoFactor::class);
    }

    #[\Override]
    public function save(PendingTwoFactor $pendingTwoFactor): void
    {
        $this->documentManager->persist($pendingTwoFactor);
        $this->documentManager->flush();
    }

    #[\Override]
    public function findById(string $id): ?PendingTwoFactor
    {
        return $this->find($id);
    }

    #[\Override]
    public function consumeIfActive(string $id, DateTimeImmutable $currentTime): bool
    {
        $result = $this->createQueryBuilder()
            ->remove()
            ->field('id')->equals($id)
            ->field('expiresAt')->gte($currentTime)
            ->getQuery()
            ->execute();

        return $this->wasDocumentRemoved($result);
    }

    #[\Override]
    public function delete(PendingTwoFactor $pendingTwoFactor): void
    {
        $this->documentManager->remove($pendingTwoFactor);
        $this->documentManager->flush();
    }

    private function wasDocumentRemoved(mixed $result): bool
    {
        if (is_int($result)) {
            return $result > 0;
        }

        if (!is_object($result) || !method_exists($result, 'getDeletedCount')) {
            return false;
        }

        $deletedCount = $result->getDeletedCount();

        return is_int($deletedCount) && $deletedCount > 0;
    }
}
