<?php

declare(strict_types=1);

namespace App\OAuth\Infrastructure\Provider;

use App\OAuth\Application\Provider\OAuthProviderInterface;
use App\OAuth\Domain\Exception\OAuthProviderException;
use App\OAuth\Domain\Exception\UnverifiedProviderEmailException;
use App\OAuth\Domain\ValueObject\OAuthProvider;
use App\OAuth\Domain\ValueObject\OAuthUserProfile;
use League\OAuth2\Client\Provider\Google;
use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Token\AccessToken;

final class GoogleOAuthProvider implements OAuthProviderInterface
{
    private const PROVIDER_NAME = 'google';

    public function __construct(
        private readonly Google $google,
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
        return true;
    }

    #[\Override]
    public function requiresExtraProfileCall(): bool
    {
        return false;
    }

    #[\Override]
    public function getAuthorizationUrl(
        string $state,
        ?string $codeChallenge,
    ): string {
        $options = [
            'state' => $state,
            'scope' => ['openid', 'email', 'profile'],
        ];

        if ($codeChallenge !== null) {
            $options['code_challenge'] = $codeChallenge;
            $options['code_challenge_method'] = 'S256';
        }

        return $this->google->getAuthorizationUrl($options);
    }

    #[\Override]
    public function exchangeCode(
        string $code,
        ?string $codeVerifier,
    ): string {
        try {
            $google = clone $this->google;

            if ($codeVerifier !== null) {
                $google->setPkceCode($codeVerifier);
            }

            $token = $google->getAccessToken(
                'authorization_code',
                ['code' => $code],
            );

            return $token->getToken();
        } catch (\Throwable $e) {
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
        } catch (UnverifiedProviderEmailException $e) {
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

        /** @var GoogleUser $owner */
        $owner = $this->google->getResourceOwner($token);

        if ($owner->getEmail() === null || $owner->getEmailVerified() !== true) {
            throw new UnverifiedProviderEmailException(self::PROVIDER_NAME);
        }

        return new OAuthUserProfile(
            email: $owner->getEmail(),
            name: $owner->getName() ?? '',
            providerId: (string) $owner->getId(),
            emailVerified: true,
        );
    }
}
