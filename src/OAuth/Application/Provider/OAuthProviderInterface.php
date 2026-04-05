<?php

declare(strict_types=1);

namespace App\OAuth\Application\Provider;

use App\OAuth\Domain\ValueObject\OAuthProvider;
use App\OAuth\Domain\ValueObject\OAuthUserProfile;

interface OAuthProviderInterface
{
    public function getProvider(): OAuthProvider;

    /** @psalm-api */
    public function supportsPkce(): bool;

    /** @psalm-api */
    public function emailAlwaysVerified(): bool;

    /** @psalm-api */
    public function requiresExtraProfileCall(): bool;

    /** @psalm-api */
    public function getAuthorizationUrl(
        string $state,
        ?string $codeChallenge,
    ): string;

    /** @psalm-api */
    public function exchangeCode(
        string $code,
        ?string $codeVerifier,
    ): string;

    /** @psalm-api */
    public function fetchProfile(string $accessToken): OAuthUserProfile;
}
