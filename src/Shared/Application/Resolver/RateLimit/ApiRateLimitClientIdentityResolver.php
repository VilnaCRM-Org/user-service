<?php

declare(strict_types=1);

namespace App\Shared\Application\Resolver\RateLimit;

use App\Shared\Application\Decoder\JwtTokenDecoderInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class ApiRateLimitClientIdentityResolver
{
    private const AUTH_COOKIE_NAME = '__Host-auth_token';
    private const EMAIL_KEY = 'email';
    private const JWT_ISSUER = 'vilnacrm-user-service';
    private const JWT_AUDIENCE = 'vilnacrm-api';
    private const PENDING_SESSION_ID_KEYS = ['pendingSessionId', 'pending_session_id'];

    public function __construct(
        private ?JwtTokenDecoderInterface $jwtDecoder = null
    ) {
    }

    public function isAuthenticatedRequest(Request $request): bool
    {
        return $this->resolveValidatedJwtPayload($request) !== null;
    }

    public function resolveClientId(Request $request): string
    {
        $clientIdFromPayload = $this->resolvePayloadValue($request, ['client_id']);
        if ($clientIdFromPayload !== null) {
            return $clientIdFromPayload;
        }

        $clientIdFromBasicAuth = $this->resolveClientIdFromBasicAuth($request);
        if ($clientIdFromBasicAuth !== null) {
            return $clientIdFromBasicAuth;
        }

        return 'anonymous';
    }

    public function resolveSignInEmail(Request $request): ?string
    {
        $email = $this->resolvePayloadValue($request, [self::EMAIL_KEY]);
        if ($email === null) {
            return null;
        }

        return strtolower(trim($email));
    }

    public function resolvePendingSessionId(Request $request): ?string
    {
        return $this->resolvePayloadValue($request, self::PENDING_SESSION_ID_KEYS);
    }

    public function resolveUserSubject(Request $request): ?string
    {
        $payload = $this->resolveValidatedJwtPayload($request);
        if ($payload === null) {
            return null;
        }

        return $this->extractSubjectFromPayload($payload);
    }

    /**
     * @param list<string> $keys
     */
    public function resolvePayloadValue(Request $request, array $keys): ?string
    {
        $rawPayload = trim($request->getContent());
        $jsonValue = $this->resolveJsonPayloadValue($rawPayload, $keys);
        if ($jsonValue !== null) {
            return $jsonValue;
        }

        return $this->resolveFormPayloadValue($rawPayload, $keys);
    }

    /**
     * @param array<string, array<int, string>|bool|float|int|string|null> $payload
     */
    private function extractSubjectFromPayload(array $payload): ?string
    {
        $subject = $payload['sub'] ?? null;

        return is_string($subject) ? $subject : null;
    }

    /**
     * @param list<string> $keys
     */
    private function resolveJsonPayloadValue(
        string $rawPayload,
        array $keys
    ): ?string {
        $jsonPayload = json_decode($rawPayload, true);
        if (!is_array($jsonPayload)) {
            return null;
        }

        return $this->findStringValue($jsonPayload, $keys);
    }

    /**
     * @param list<string> $keys
     */
    private function resolveFormPayloadValue(
        string $rawPayload,
        array $keys
    ): ?string {
        parse_str($rawPayload, $formPayload);

        return $this->findStringValue($formPayload, $keys);
    }

    /**
     * @param array<string, array<int, string>|bool|float|int|string|null> $payload
     * @param list<string> $keys
     */
    private function findStringValue(array $payload, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = $payload[$key] ?? null;
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function resolveClientIdFromBasicAuth(Request $request): ?string
    {
        $authorization = $request->headers->get('Authorization');
        if (!is_string($authorization) || !str_starts_with($authorization, 'Basic ')) {
            return null;
        }

        $decoded = base64_decode(substr($authorization, strlen('Basic ')), true);
        if (!is_string($decoded) || $decoded === '') {
            return null;
        }

        $colonPos = strpos($decoded, ':');
        $clientId = $colonPos !== false ? substr($decoded, 0, $colonPos) : $decoded;

        return $clientId !== '' ? $clientId : null;
    }

    private function resolveBearerToken(Request $request): ?string
    {
        $header = (string) $request->headers->get('Authorization');
        if (preg_match('/^Bearer\s+(\S+)/i', $header, $matches) === 1) {
            return $matches[1];
        }

        $cookieToken = $request->cookies->get(self::AUTH_COOKIE_NAME);
        if (is_string($cookieToken) && $cookieToken !== '') {
            return $cookieToken;
        }

        return null;
    }

    /**
     * @return array<string, array<int, string>|bool|float|int|string|null>|null
     */
    private function resolveValidatedJwtPayload(Request $request): ?array
    {
        if (!$this->jwtDecoder instanceof JwtTokenDecoderInterface) {
            return null;
        }

        $token = $this->resolveBearerToken($request);
        if ($token === null) {
            return null;
        }

        $payload = $this->jwtDecoder->decode($token);
        if (!is_array($payload)) {
            return null;
        }

        return $this->hasExpectedClaims($payload) ? $payload : null;
    }

    /**
     * @param array<string, array<int, string>|bool|float|int|string|null> $payload
     */
    private function hasExpectedClaims(array $payload): bool
    {
        if (!$this->hasExpectedIssuer($payload) || !$this->hasExpectedAudience($payload)) {
            return false;
        }

        $subject = $payload['sub'] ?? null;
        if (!is_string($subject) || $subject === '') {
            return false;
        }

        $notBefore = $payload['nbf'] ?? null;
        $expiresAt = $payload['exp'] ?? null;
        if (!is_int($notBefore) || !is_int($expiresAt)) {
            return false;
        }

        $now = time();

        return $notBefore <= $now && $expiresAt > $now;
    }

    /**
     * @param array<string, array<int, string>|bool|float|int|string|null> $payload
     */
    private function hasExpectedIssuer(array $payload): bool
    {
        return ($payload['iss'] ?? null) === self::JWT_ISSUER;
    }

    /**
     * @param array<string, array<int, string>|bool|float|int|string|null> $payload
     */
    private function hasExpectedAudience(array $payload): bool
    {
        $audience = $payload['aud'] ?? null;

        if (is_string($audience)) {
            return $audience === self::JWT_AUDIENCE;
        }

        return is_array($audience) && in_array(self::JWT_AUDIENCE, $audience, true);
    }
}
