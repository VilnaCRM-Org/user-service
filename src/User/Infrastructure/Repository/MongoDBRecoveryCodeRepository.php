<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Repository;

use App\User\Domain\Entity\RecoveryCode;
use App\User\Domain\Repository\RecoveryCodeRepositoryInterface;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;
use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * @extends ServiceDocumentRepository<RecoveryCode>
 *
 * @psalm-suppress UnusedClass - Used via dependency injection
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

    /**
     * @return array<RecoveryCode>
     */
    #[\Override]
    public function findByUserId(string $userId): array
    {
        $result = $this->findBy(['userId' => $userId]);

        return array_values($result);
    }

    #[\Override]
    public function delete(RecoveryCode $recoveryCode): void
    {
        $this->documentManager->remove($recoveryCode);
        $this->documentManager->flush();
    }

    /**
     * @return int
     *
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
}
