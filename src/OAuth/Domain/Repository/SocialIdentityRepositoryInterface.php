<?php

declare(strict_types=1);

namespace App\OAuth\Domain\Repository;

use App\OAuth\Domain\Entity\SocialIdentity;
use App\OAuth\Domain\ValueObject\OAuthProvider;

interface SocialIdentityRepositoryInterface
{
    public function save(SocialIdentity $socialIdentity): void;

    public function findByProviderAndProviderId(
        OAuthProvider $provider,
        string $providerId,
    ): ?SocialIdentity;

    public function findByUserIdAndProvider(
        string $userId,
        OAuthProvider $provider,
    ): ?SocialIdentity;
}
