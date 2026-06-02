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
        return $this->claimActiveMatching($id, $purpose, null, $consumedAt);
    }

    #[\Override]
    public function claimActiveForUser(
        string $id,
        string $purpose,
        string $userId,
        DateTimeImmutable $consumedAt
    ): ?PasskeyChallenge {
        return $this->claimActiveMatching($id, $purpose, $userId, $consumedAt);
    }

    #[\Override]
    public function delete(PasskeyChallenge $challenge): void
    {
        $this->documentManager->remove($challenge);
        $this->documentManager->flush();
    }

    private function claimActiveMatching(
        string $id,
        string $purpose,
        ?string $userId,
        DateTimeImmutable $consumedAt
    ): ?PasskeyChallenge {
        $queryBuilder = $this->createQueryBuilder()
            ->updateOne()
            ->field('id')->equals($id)
            ->field('purpose')->equals($purpose)
            ->field('expiresAt')->gt($consumedAt)
            ->field('consumedAt')->equals(null);

        if ($userId !== null) {
            $queryBuilder->field('userId')->equals($userId);
        }

        $result = $queryBuilder
            ->field('consumedAt')->set($consumedAt)
            ->getQuery()
            ->execute();

        if (!$this->writeResultCounter->wasDocumentUpdated($result)) {
            return null;
        }

        $this->documentManager->clear(PasskeyChallenge::class);

        return $this->find($id);
    }
}
