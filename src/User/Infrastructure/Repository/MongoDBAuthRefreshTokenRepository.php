<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Repository;

use App\User\Domain\Entity\AuthRefreshToken;
use App\User\Domain\Repository\AuthRefreshTokenRepositoryInterface;
use DateTimeImmutable;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;
use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * @extends ServiceDocumentRepository<AuthRefreshToken>
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

    #[\Override]
    public function findByPlainToken(string $plainToken): ?AuthRefreshToken
    {
        return $this->findByTokenHash(hash('sha256', $plainToken));
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

    #[\Override]
    public function markAsRotatedIfActive(
        string $tokenHash,
        DateTimeImmutable $rotatedAt
    ): bool {
        $result = $this->createQueryBuilder()
            ->updateOne()
            ->field('tokenHash')->equals($tokenHash)
            ->field('rotatedAt')->equals(null)
            ->field('revokedAt')->equals(null)
            ->field('expiresAt')->gt($rotatedAt)
            ->field('rotatedAt')->set($rotatedAt)
            ->getQuery()
            ->execute();

        return $this->wasDocumentUpdated($result);
    }

    #[\Override]
    public function markGraceUsedIfEligible(
        string $tokenHash,
        DateTimeImmutable $graceWindowStartedAt,
        DateTimeImmutable $currentTime
    ): bool {
        $result = $this->createQueryBuilder()
            ->updateOne()
            ->field('tokenHash')->equals($tokenHash)
            ->field('rotatedAt')->gte($graceWindowStartedAt)
            ->field('graceUsed')->equals(false)
            ->field('revokedAt')->equals(null)
            ->field('expiresAt')->gt($currentTime)
            ->field('graceUsed')->set(true)
            ->getQuery()
            ->execute();

        return $this->wasDocumentUpdated($result);
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
}
