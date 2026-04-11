<?php

declare(strict_types=1);

namespace App\OAuth\Infrastructure\Provider;

use App\OAuth\Application\Provider\OAuthProviderInterface;
use App\OAuth\Domain\Exception\OAuthEmailUnavailableException;
use App\OAuth\Domain\Exception\OAuthProviderException;
use App\OAuth\Domain\ValueObject\OAuthProvider;
use App\OAuth\Domain\ValueObject\OAuthUserProfile;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\Facebook;
use League\OAuth2\Client\Token\AccessToken;

final class FacebookOAuthProvider implements OAuthProviderInterface
{
    private const PROVIDER_NAME = 'facebook';

    public function __construct(
        private readonly Facebook $facebook,
        private readonly OAuthProvider $provider,
    ) {
    }

    #[\Override]
    public function getProvider(): OAuthProvider
    {
        return $this->provider;
    }

    #[\Override]
    public function supportsPkce(): bool
    {
        return true;
    }

    #[\Override]
    public function emailAlwaysVerified(): bool
    {
        return false;
    }

    #[\Override]
    public function requiresExtraProfileCall(): bool
    {
        return true;
    }

    #[\Override]
    public function getAuthorizationUrl(
        string $state,
        ?string $codeChallenge,
    ): string {
        $options = [
            'state' => $state,
            'scope' => ['email'],
        ];

        if ($codeChallenge !== null) {
            $options['code_challenge'] = $codeChallenge;
            $options['code_challenge_method'] = 'S256';
        }

        return $this->facebook->getAuthorizationUrl($options);
    }

    #[\Override]
    public function exchangeCode(
        string $code,
        ?string $codeVerifier,
    ): string {
        try {
            if ($codeVerifier !== null) {
                $this->facebook->setPkceCode($codeVerifier);
            }

            $token = $this->facebook->getAccessToken(
                'authorization_code',
                ['code' => $code],
            );

            return $token->getToken();
        } catch (IdentityProviderException $e) {
            throw new OAuthProviderException(
                self::PROVIDER_NAME,
                $e->getMessage(),
                $e,
            );
        }
    }

    #[\Override]
    public function fetchProfile(string $accessToken): OAuthUserProfile
    {
        try {
            return $this->buildProfile($accessToken);
        } catch (OAuthEmailUnavailableException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new OAuthProviderException(
                self::PROVIDER_NAME,
                $e->getMessage(),
                $e,
            );
        }
    }

    private function buildProfile(string $accessToken): OAuthUserProfile
    {
        $token = new AccessToken(['access_token' => $accessToken]);

        $owner = $this->facebook->getResourceOwner($token);

        if ($owner->getEmail() === null) {
            throw new OAuthEmailUnavailableException(self::PROVIDER_NAME);
        }

        return new OAuthUserProfile(
            email: $owner->getEmail(),
            name: $owner->getName() ?? '',
            providerId: (string) $owner->getId(),
            emailVerified: true,
        );
    }
}
