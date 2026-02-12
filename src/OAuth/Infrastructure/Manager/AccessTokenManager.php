<?php

declare(strict_types=1);

namespace App\OAuth\Infrastructure\Manager;

use DateTimeImmutable;
use Doctrine\ODM\MongoDB\DocumentManager;
use League\Bundle\OAuth2ServerBundle\Manager\AccessTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\AccessToken;
use League\Bundle\OAuth2ServerBundle\Model\AccessTokenInterface;
use League\Bundle\OAuth2ServerBundle\Model\RefreshToken;

/**
 * @psalm-suppress UnusedClass - Used via dependency injection
 */
final class AccessTokenManager implements AccessTokenManagerInterface
{
    public function __construct(
        private readonly DocumentManager $documentManager,
        private readonly bool $persistAccessToken,
    ) {
    }

    /**
     * @return AccessToken|null
     */
    #[\Override]
    public function find(string $identifier): ?AccessTokenInterface
    {
        if (! $this->persistAccessToken) {
            return null;
        }

        return $this->documentManager->find(AccessToken::class, $identifier);
    }

    #[\Override]
    public function save(AccessTokenInterface $accessToken): void
    {
        if (! $this->persistAccessToken) {
            return;
        }

        $this->documentManager->persist($accessToken);
        $this->documentManager->flush();
    }

    /**
     * @return int
     *
     * @psalm-return int<0, max>
     */
    #[\Override]
    public function clearExpired(): int
    {
        if (! $this->persistAccessToken) {
            return 0;
        }

        $identifiers = $this->findExpiredTokenIdentifiers();
        if ($identifiers === []) {
            return 0;
        }

        $this->unlinkRefreshTokens($identifiers);
        $this->removeAccessTokens($identifiers);

        return count($identifiers);
    }

    /**
     * @return list<string>
     */
    private function findExpiredTokenIdentifiers(): array
    {
        $expiredTokens = $this->documentManager->createQueryBuilder(AccessToken::class)
            ->field('expiry')->lt(new DateTimeImmutable())
            ->getQuery()
            ->execute();

        return array_values(array_map(
            static fn (AccessTokenInterface $token): string => $token->getIdentifier(),
            iterator_to_array($expiredTokens, false)
        ));
    }

    /**
     * @param list<string> $identifiers
     */
    private function unlinkRefreshTokens(array $identifiers): void
    {
        $this->documentManager->createQueryBuilder(RefreshToken::class)
            ->updateMany()
            ->field('accessToken')->in($identifiers)
            ->field('accessToken')->set(null)
            ->getQuery()
            ->execute();
    }

    /**
     * @param list<string> $identifiers
     */
    private function removeAccessTokens(array $identifiers): void
    {
        $this->documentManager->createQueryBuilder(AccessToken::class)
            ->remove()
            ->field('identifier')->in($identifiers)
            ->getQuery()
            ->execute();
    }
}
