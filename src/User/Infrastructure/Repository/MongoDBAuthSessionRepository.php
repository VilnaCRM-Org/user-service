<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Repository;

use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;
use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * @extends ServiceDocumentRepository<AuthSession>
 *
 * @psalm-suppress UnusedClass - Used via dependency injection
 */
final class MongoDBAuthSessionRepository extends ServiceDocumentRepository implements
    AuthSessionRepositoryInterface
{
    public function __construct(
        private readonly DocumentManager $documentManager,
        ManagerRegistry $registry,
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
    public function delete(AuthSession $authSession): void
    {
        $this->documentManager->remove($authSession);
        $this->documentManager->flush();
    }
}
