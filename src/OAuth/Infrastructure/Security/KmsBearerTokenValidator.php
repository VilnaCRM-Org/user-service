<?php

declare(strict_types=1);

namespace App\OAuth\Infrastructure\Security;

use App\Shared\Application\Provider\CurrentTimestampProviderInterface;
use App\Shared\Infrastructure\Validator\JwtSignatureVerifier;
use League\OAuth2\Server\AuthorizationValidators\AuthorizationValidatorInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * League resource-server validator that verifies bearer JWTs with the KMS
 * public key their "kid" names (current or previous key), instead of league's
 * single local public key.
 */
final readonly class KmsBearerTokenValidator implements AuthorizationValidatorInterface
{
    private const BEARER_PREFIX = 'Bearer ';

    public function __construct(
        private AccessTokenRepositoryInterface $accessTokenRepository,
        private JwtSignatureVerifier $verifier,
        private CurrentTimestampProviderInterface $timestampProvider,
    ) {
    }

    #[\Override]
    public function validateAuthorization(ServerRequestInterface $request): ServerRequestInterface
    {
        $claims = $this->verifiedClaims($this->bearerToken($request));
        $tokenId = $claims['jti'] ?? null;
        if (!is_string($tokenId) || $this->accessTokenRepository->isAccessTokenRevoked($tokenId)) {
            throw $this->accessDenied('Access token has been revoked');
        }

        return $request
            ->withAttribute('oauth_access_token_id', $tokenId)
            ->withAttribute('oauth_client_id', $claims['aud'] ?? null)
            ->withAttribute('oauth_user_id', $claims['sub'] ?? null)
            ->withAttribute('oauth_scopes', $claims['scopes'] ?? []);
    }

    private function bearerToken(ServerRequestInterface $request): string
    {
        $authorization = $request->getHeaderLine('authorization');
        $token = str_starts_with($authorization, self::BEARER_PREFIX)
            ? substr($authorization, strlen(self::BEARER_PREFIX))
            : '';
        if ($token === '') {
            throw $this->accessDenied('Missing "Bearer" token');
        }

        return $token;
    }

    /**
     * @return array<string, array<array-key, bool|float|int|string|null>|bool|float|int|string|null>
     */
    private function verifiedClaims(string $token): array
    {
        $claims = $this->verifier->verify($token)['payload'] ?? null;
        if ($claims === null || !$this->isActive($claims)) {
            throw $this->accessDenied('Access token could not be verified');
        }

        return $claims;
    }

    /**
     * @param array<string, array<array-key, bool|float|int|string|null>|bool|float|int|string|null> $claims
     */
    private function isActive(array $claims): bool
    {
        $now = $this->timestampProvider->currentTimestamp();
        $expiresAt = $claims['exp'] ?? null;
        $notBefore = $claims['nbf'] ?? $now;

        return is_int($expiresAt) && $expiresAt > $now
            && is_int($notBefore) && $notBefore <= $now;
    }

    private function accessDenied(string $hint): OAuthServerException
    {
        return new OAuthServerException(
            'The resource owner or authorization server denied the request.',
            9,
            'access_denied',
            401,
            $hint
        );
    }
}
