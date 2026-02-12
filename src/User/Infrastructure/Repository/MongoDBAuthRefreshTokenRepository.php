<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Repository;

use App\User\Domain\Entity\AuthRefreshToken;
use App\User\Domain\Repository\AuthRefreshTokenRepositoryInterface;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;
use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * @extends ServiceDocumentRepository<AuthRefreshToken>
 *
 * @psalm-suppress UnusedClass - Used via dependency injection
 */
final class MongoDBAuthRefreshTokenRepository extends ServiceDocumentRepository implements
    AuthRefreshTokenRepositoryInterface
{
    public function __construct(
        private readonly DocumentManager $documentManager,
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, AuthRefreshToken::class);
    }

    #[\Override]
    public function save(AuthRefreshToken $authRefreshToken): void
    {
        $this->documentManager->persist($authRefreshToken);
        $this->documentManager->flush();
    }

    #[\Override]
    public function findById(string $id): ?AuthRefreshToken
    {
        return $this->find($id);
    }

    #[\Override]
    public function findByTokenHash(string $tokenHash): ?AuthRefreshToken
    {
        return $this->findOneBy(['tokenHash' => $tokenHash]);
    }

    /**
     * @return list<AuthRefreshToken>
     */
    #[\Override]
    public function findBySessionId(string $sessionId): array
    {
        return $this->findBy(['sessionId' => $sessionId]);
    }

    #[\Override]
    public function delete(AuthRefreshToken $authRefreshToken): void
    {
        $this->documentManager->remove($authRefreshToken);
        $this->documentManager->flush();
    }

    #[\Override]
    public function revokeBySessionId(string $sessionId): void
    {
        $tokens = $this->findBySessionId($sessionId);

        foreach ($tokens as $token) {
            if ($token->getRevokedAt() === null) {
                $token->revoke();
                $this->documentManager->persist($token);
            }
        }

        $this->documentManager->flush();
    }
}
