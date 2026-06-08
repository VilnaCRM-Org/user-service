<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Repository;

use App\User\Domain\Collection\PasskeyCredentialCollection;
use App\User\Domain\Entity\PasskeyCredential;
use App\User\Domain\Repository\PasskeyCredentialRepositoryInterface;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;
use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * @extends ServiceDocumentRepository<PasskeyCredential>
 *
 * @psalm-api
 */
final class MongoDBPasskeyCredentialRepository extends ServiceDocumentRepository implements
    PasskeyCredentialRepositoryInterface
{
    public function __construct(
        private readonly DocumentManager $documentManager,
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, PasskeyCredential::class);
    }

    #[\Override]
    public function save(PasskeyCredential $credential): void
    {
        $this->documentManager->persist($credential);
        $this->documentManager->flush();
    }

    #[\Override]
    public function delete(PasskeyCredential $credential): void
    {
        $this->documentManager->remove($credential);
        $this->documentManager->flush();
    }

    #[\Override]
    public function findByCredentialId(string $credentialId): ?PasskeyCredential
    {
        return $this->findOneBy(['credentialId' => $credentialId]);
    }

    #[\Override]
    public function findByUserId(string $userId): PasskeyCredentialCollection
    {
        return new PasskeyCredentialCollection(
            ...array_values($this->findBy(['userId' => $userId]))
        );
    }

    #[\Override]
    public function existsByCredentialId(string $credentialId): bool
    {
        return (int) $this->createQueryBuilder()
            ->field('credentialId')
            ->equals($credentialId)
            ->count()
            ->getQuery()
            ->execute() > 0;
    }
}
