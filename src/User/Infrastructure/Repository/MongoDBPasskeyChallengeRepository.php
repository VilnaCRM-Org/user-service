<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Repository;

use App\User\Domain\Entity\PasskeyChallenge;
use App\User\Domain\Repository\PasskeyChallengeRepositoryInterface;
use DateTimeImmutable;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;
use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * @extends ServiceDocumentRepository<PasskeyChallenge>
 *
 * @psalm-api
 */
final class MongoDBPasskeyChallengeRepository extends ServiceDocumentRepository implements
    PasskeyChallengeRepositoryInterface
{
    public function __construct(
        private readonly DocumentManager $documentManager,
        ManagerRegistry $registry,
        private readonly MongoDBWriteResultCounter $writeResultCounter,
    ) {
        parent::__construct($registry, PasskeyChallenge::class);
    }

    #[\Override]
    public function save(PasskeyChallenge $challenge): void
    {
        $this->documentManager->persist($challenge);
        $this->documentManager->flush();
    }

    #[\Override]
    public function findById(string $id): ?PasskeyChallenge
    {
        return $this->find($id);
    }

    #[\Override]
    public function claimActive(
        string $id,
        string $purpose,
        DateTimeImmutable $consumedAt
    ): ?PasskeyChallenge {
        $result = $this->createQueryBuilder()
            ->updateOne()
            ->field('id')->equals($id)
            ->field('purpose')->equals($purpose)
            ->field('expiresAt')->gte($consumedAt)
            ->field('consumedAt')->equals(null)
            ->field('consumedAt')->set($consumedAt)
            ->getQuery()
            ->execute();

        if (!$this->writeResultCounter->wasDocumentUpdated($result)) {
            return null;
        }

        $this->documentManager->clear(PasskeyChallenge::class);

        return $this->find($id);
    }

    #[\Override]
    public function delete(PasskeyChallenge $challenge): void
    {
        $this->documentManager->remove($challenge);
        $this->documentManager->flush();
    }
}
