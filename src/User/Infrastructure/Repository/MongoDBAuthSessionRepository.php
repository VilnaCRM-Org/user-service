<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Repository;

use App\User\Domain\Collection\AuthSessionCollection;
use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use DateTimeImmutable;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;
use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * @extends ServiceDocumentRepository<AuthSession>
 */
final class MongoDBAuthSessionRepository extends ServiceDocumentRepository implements
    AuthSessionRepositoryInterface
{
    public function __construct(
        private readonly DocumentManager $documentManager,
        ManagerRegistry $registry,
        private readonly MongoDBWriteResultCounter $writeResultCounter,
    ) {
        parent::__construct($registry, AuthSession::class);
    }

    #[\Override]
    public function save(AuthSession $authSession): void
    {
        $this->documentManager->persist($authSession);
        $this->documentManager->flush();
    }

    #[\Override]
    public function findById(string $id): ?AuthSession
    {
        return $this->find($id);
    }

    #[\Override]
    public function findByUserId(string $userId): AuthSessionCollection
    {
        $result = $this->findBy(['userId' => $userId]);

        return new AuthSessionCollection(...array_values($result));
    }

    #[\Override]
    public function delete(AuthSession $authSession): void
    {
        $this->documentManager->remove($authSession);
        $this->documentManager->flush();
    }

    #[\Override]
    public function revokeOtherActiveByUserId(
        string $userId,
        string $currentSessionId,
        DateTimeImmutable $revokedAt
    ): int {
        $result = $this->createQueryBuilder()
            ->updateMany()
            ->field('userId')->equals($userId)
            ->field('id')->notEqual($currentSessionId)
            ->field('revokedAt')->equals(null)
            ->field('revokedAt')->set($revokedAt)
            ->getQuery()
            ->execute();

        $modifiedCount = $this->writeResultCounter->modifiedDocumentCount($result);
        if ($modifiedCount > 0) {
            $this->documentManager->clear(AuthSession::class);
        }

        return $modifiedCount;
    }
}
