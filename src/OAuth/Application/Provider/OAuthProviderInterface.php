<?php

declare(strict_types=1);

namespace App\OAuth\Application\Provider;

use App\OAuth\Domain\ValueObject\OAuthProvider;
use App\OAuth\Domain\ValueObject\OAuthUserProfile;

interface OAuthProviderInterface
{
    public function getProvider(): OAuthProvider;

    /** @psalm-suppress PossiblyUnusedMethod Used by Epic 2 command handlers */
    public function supportsPkce(): bool;

    /** @psalm-suppress PossiblyUnusedMethod Used by Epic 2 command handlers */
    public function emailAlwaysVerified(): bool;

    /** @psalm-suppress PossiblyUnusedMethod Used by Epic 2 command handlers */
    public function requiresExtraProfileCall(): bool;

    /** @psalm-suppress PossiblyUnusedMethod Used by Epic 2 command handlers */
    public function getAuthorizationUrl(
        string $state,
        ?string $codeChallenge,
    ): string;

    /** @psalm-suppress PossiblyUnusedMethod Used by Epic 2 command handlers */
    public function exchangeCode(
        string $code,
        ?string $codeVerifier,
    ): string;

    /** @psalm-suppress PossiblyUnusedMethod Used by Epic 2 command handlers */
    public function fetchProfile(string $accessToken): OAuthUserProfile;
}
