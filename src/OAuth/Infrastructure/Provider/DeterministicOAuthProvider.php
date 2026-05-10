<?php

declare(strict_types=1);

namespace App\OAuth\Infrastructure\Provider;

use App\OAuth\Application\Provider\OAuthProviderInterface;
use App\OAuth\Domain\Exception\OAuthEmailUnavailableException;
use App\OAuth\Domain\Exception\OAuthProviderException;
use App\OAuth\Domain\Exception\UnverifiedProviderEmailException;
use App\OAuth\Domain\ValueObject\OAuthProvider;
use App\OAuth\Domain\ValueObject\OAuthUserProfile;

final readonly class DeterministicOAuthProvider implements OAuthProviderInterface
{
    private const PROVIDER_ERROR_CODE = 'provider-error';
    private const NO_EMAIL_CODE = 'no-email';
    private const UNVERIFIED_EMAIL_CODE = 'unverified-email';

    public function __construct(
        private OAuthProvider $provider,
        private bool $emailAlwaysVerified,
        private bool $requiresExtraProfileCall,
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
        return $this->emailAlwaysVerified;
    }

    #[\Override]
    public function requiresExtraProfileCall(): bool
    {
        return $this->requiresExtraProfileCall;
    }

    #[\Override]
    public function getAuthorizationUrl(
        string $state,
        ?string $codeChallenge,
    ): string {
        $query = [
            'state' => $state,
            'provider' => (string) $this->provider,
        ];

        if ($codeChallenge !== null) {
            $query['code_challenge'] = $codeChallenge;
            $query['code_challenge_method'] = 'S256';
        }

        return sprintf(
            'https://oauth.mock.example/%s/authorize?%s',
            (string) $this->provider,
            http_build_query($query),
        );
    }

    #[\Override]
    public function exchangeCode(
        string $code,
        ?string $codeVerifier,
    ): string {
        if ($code === self::PROVIDER_ERROR_CODE) {
            throw new OAuthProviderException(
                (string) $this->provider,
                'Mock provider token exchange failed.',
            );
        }

        return sprintf(
            '%s:%s',
            (string) $this->provider,
            rawurlencode($code),
        );
    }

    #[\Override]
    public function fetchProfile(string $accessToken): OAuthUserProfile
    {
        $scenario = $this->extractScenario($accessToken);

        return match ($scenario) {
            self::NO_EMAIL_CODE => throw new OAuthEmailUnavailableException(
                (string) $this->provider,
            ),
            self::UNVERIFIED_EMAIL_CODE => throw new UnverifiedProviderEmailException(
                (string) $this->provider,
            ),
            default => $this->buildProfile($scenario),
        };
    }

    public static function emailFor(
        string $provider,
        string $scenario,
    ): string {
        return sprintf(
            '%s@oauth.example.test',
            self::slugify(sprintf('%s-%s', $provider, $scenario)),
        );
    }

    public static function providerIdFor(
        string $provider,
        string $scenario,
    ): string {
        return sprintf(
            '%s-id',
            self::slugify(sprintf('%s-%s', $provider, $scenario)),
        );
    }

    private function extractScenario(string $accessToken): string
    {
        $separatorPosition = strpos($accessToken, ':');
        if ($separatorPosition === false) {
            return self::slugify((string) $this->provider);
        }

        $scenario = substr($accessToken, $separatorPosition + 1);

        return rawurldecode($scenario);
    }

    private function buildProfile(string $scenario): OAuthUserProfile
    {
        $provider = (string) $this->provider;

        return new OAuthUserProfile(
            self::emailFor($provider, $scenario),
            ucfirst(str_replace('-', ' ', self::slugify($scenario))),
            self::providerIdFor($provider, $scenario),
            true,
        );
    }

    private static function slugify(string $value): string
    {
        $normalized = preg_replace('/[^a-z0-9]+/i', '-', strtolower($value)) ?? '';
        $trimmed = trim($normalized, '-');

        return $trimmed !== '' ? $trimmed : 'oauth-user';
    }
}
