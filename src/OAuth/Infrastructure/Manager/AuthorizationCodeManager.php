<?php

declare(strict_types=1);

namespace App\OAuth\Infrastructure\Manager;

use DateTimeImmutable;
use Doctrine\ODM\MongoDB\DocumentManager;
use League\Bundle\OAuth2ServerBundle\Manager\AuthorizationCodeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\AuthorizationCode;
use League\Bundle\OAuth2ServerBundle\Model\AuthorizationCodeInterface;

/**
 * @psalm-suppress UnusedClass - Used via dependency injection
 */
final class AuthorizationCodeManager implements AuthorizationCodeManagerInterface
{
    public function __construct(
        private readonly DocumentManager $documentManager,
    ) {
    }

    #[\Override]
    public function find(string $identifier): ?AuthorizationCodeInterface
    {
        return $this->documentManager->find(AuthorizationCode::class, $identifier);
    }

    #[\Override]
    public function save(AuthorizationCodeInterface $authCode): void
    {
        $this->documentManager->persist($authCode);
        $this->documentManager->flush();
        $this->documentManager->refresh($authCode);
    }

    #[\Override]
    public function clearExpired(): int
    {
        $result = $this->documentManager->createQueryBuilder(AuthorizationCode::class)
            ->remove()
            ->field('expiry')->lt(new DateTimeImmutable())
            ->getQuery()
            ->execute();

        return $this->deletedCount($result);
    }

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
