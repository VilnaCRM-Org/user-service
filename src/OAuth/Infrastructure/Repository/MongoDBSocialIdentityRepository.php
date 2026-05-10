<?php

declare(strict_types=1);

namespace App\OAuth\Infrastructure\Repository;

use App\OAuth\Domain\Entity\SocialIdentity;
use App\OAuth\Domain\Repository\SocialIdentityRepositoryInterface;
use App\OAuth\Domain\ValueObject\OAuthProvider;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;
use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * @extends ServiceDocumentRepository<SocialIdentity>
 *
 * @psalm-suppress UnusedClass - Used via dependency injection
 */
final class MongoDBSocialIdentityRepository extends ServiceDocumentRepository implements
    SocialIdentityRepositoryInterface
{
    public function __construct(
        private readonly DocumentManager $documentManager,
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, SocialIdentity::class);
    }

    #[\Override]
    public function save(SocialIdentity $socialIdentity): void
    {
        $this->documentManager->persist($socialIdentity);
        $this->documentManager->flush();
    }

    #[\Override]
    public function findByProviderAndProviderId(
        OAuthProvider $provider,
        string $providerId,
    ): ?SocialIdentity {
        return $this->findOneBy([
            'provider' => (string) $provider,
            'providerId' => $providerId,
        ]);
    }

    #[\Override]
    public function findByUserIdAndProvider(
        string $userId,
        OAuthProvider $provider,
    ): ?SocialIdentity {
        return $this->findOneBy([
            'userId' => $userId,
            'provider' => (string) $provider,
        ]);
    }
}
