<?php

declare(strict_types=1);

namespace App\OAuth\Infrastructure\Provider;

use App\OAuth\Application\Provider\OAuthProviderInterface;
use App\OAuth\Domain\Exception\OAuthProviderException;
use App\OAuth\Domain\Exception\UnverifiedProviderEmailException;
use App\OAuth\Domain\ValueObject\OAuthProvider;
use App\OAuth\Domain\ValueObject\OAuthUserProfile;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\Github;
use League\OAuth2\Client\Token\AccessToken;

final class GitHubOAuthProvider implements OAuthProviderInterface
{
    private const PROVIDER_NAME = 'github';
    private const EMAILS_ENDPOINT = 'https://api.github.com/user/emails';

    public function __construct(
        private readonly Github $github,
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
            'scope' => ['user:email'],
        ];

        if ($codeChallenge !== null) {
            $options['code_challenge'] = $codeChallenge;
            $options['code_challenge_method'] = 'S256';
        }

        return $this->github->getAuthorizationUrl($options);
    }

    #[\Override]
    public function exchangeCode(
        string $code,
        ?string $codeVerifier,
    ): string {
        try {
            if ($codeVerifier !== null) {
                $this->github->setPkceCode($codeVerifier);
            }

            $token = $this->github->getAccessToken(
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
        $owner = $this->github->getResourceOwner($token);
        $email = $this->fetchVerifiedPrimaryEmail($accessToken);

        return new OAuthUserProfile(
            email: $email,
            name: $owner->getName() ?? $owner->getNickname() ?? '',
            providerId: (string) $owner->getId(),
            emailVerified: true,
        );
    }

    private function fetchVerifiedPrimaryEmail(string $accessToken): string
    {
        $token = new AccessToken([
            'access_token' => $accessToken,
        ]);

        $request = $this->github->getAuthenticatedRequest(
            'GET',
            self::EMAILS_ENDPOINT,
            $token,
        );

        /** @var list<array{email: string, primary: bool, verified: bool}> $emails */
        $emails = $this->github->getParsedResponse($request);

        foreach ($emails as $emailEntry) {
            if ($emailEntry['primary'] && $emailEntry['verified']) {
                return $emailEntry['email'];
            }
        }

        throw new UnverifiedProviderEmailException(self::PROVIDER_NAME);
    }
}
