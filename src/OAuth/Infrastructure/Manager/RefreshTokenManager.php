<?php

declare(strict_types=1);

namespace App\OAuth\Infrastructure\Manager;

use DateTimeImmutable;
use Doctrine\ODM\MongoDB\DocumentManager;
use League\Bundle\OAuth2ServerBundle\Manager\RefreshTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\RefreshToken;
use League\Bundle\OAuth2ServerBundle\Model\RefreshTokenInterface;

/**
 * @psalm-suppress UnusedClass - Used via dependency injection
 */
final class RefreshTokenManager implements RefreshTokenManagerInterface
{
    public function __construct(
        private readonly DocumentManager $documentManager,
    ) {
    }

    #[\Override]
    public function find(string $identifier): ?RefreshTokenInterface
    {
        return $this->documentManager->find(RefreshToken::class, $identifier);
    }

    #[\Override]
    public function save(RefreshTokenInterface $refreshToken): void
    {
        $this->documentManager->persist($refreshToken);
        $this->documentManager->flush();
    }

    #[\Override]
    public function clearExpired(): int
    {
        $result = $this->documentManager->createQueryBuilder(RefreshToken::class)
            ->remove()
            ->field('expiry')->lt(new DateTimeImmutable())
            ->getQuery()
            ->execute();

        return $this->deletedCount($result);
    }

    /**
     * @psalm-param array<string, mixed>|int|object|null $result
     */
    private function deletedCount(array|object|int|null $result): int
    {
        if (is_int($result)) {
            return $result;
        }

        if (is_object($result) && method_exists($result, 'getDeletedCount')) {
            return (int) $result->getDeletedCount();
        }

        return 0;
    }
}
