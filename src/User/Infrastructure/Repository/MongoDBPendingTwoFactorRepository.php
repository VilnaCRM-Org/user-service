<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Repository;

use App\User\Domain\Entity\PendingTwoFactor;
use App\User\Domain\Repository\PendingTwoFactorRepositoryInterface;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;
use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * @extends ServiceDocumentRepository<PendingTwoFactor>
 *
 * @psalm-suppress UnusedClass - Used via dependency injection
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
    public function delete(PendingTwoFactor $pendingTwoFactor): void
    {
        $this->documentManager->remove($pendingTwoFactor);
        $this->documentManager->flush();
    }
}
