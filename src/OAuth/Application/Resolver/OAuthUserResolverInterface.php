<?php

declare(strict_types=1);

namespace App\OAuth\Application\Resolver;

use App\OAuth\Application\DTO\OAuthResolvedUser;
use App\OAuth\Domain\ValueObject\OAuthProvider;
use App\OAuth\Domain\ValueObject\OAuthUserProfile;

interface OAuthUserResolverInterface
{
    public function resolve(
        OAuthProvider $provider,
        OAuthUserProfile $profile,
    ): OAuthResolvedUser;
}
