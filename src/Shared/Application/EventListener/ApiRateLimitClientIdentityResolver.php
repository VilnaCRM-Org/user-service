<?php

declare(strict_types=1);

namespace App\Shared\Application\EventListener;

use Symfony\Component\HttpFoundation\Request;

/** @infection-ignore-all */
final readonly class ApiRateLimitClientIdentityResolver
{
    private const AUTH_COOKIE_NAME = '__Host-auth_token';
    private const EMAIL_KEY = 'email';
    private const PENDING_SESSION_ID_KEYS = ['pendingSessionId', 'pending_session_id'];

    public function isAuthenticatedRequest(Request $request): bool
    {
        $authorizationHeader = $request->headers->get('Authorization');
        $hasBearerToken = is_string($authorizationHeader)
            && preg_match('/^Bearer\s+\S+/i', $authorizationHeader) === 1;

        return $hasBearerToken || $request->cookies->has(self::AUTH_COOKIE_NAME);
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

    /**
     * @return null|string
     */
    public function resolveSignInEmail(Request $request): string|null|null
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

    public function resolveUserSubject(Request $request): string|null|null
    {
        $token = $this->resolveBearerToken($request);
        if ($token === null) {
            return null;
        }

        $parts = explode('.', $token);
        if (count($parts) < 2) {
            return null;
        }

        $payloadJson = $this->decodeBase64Url($parts[1]);
        if (!is_string($payloadJson) || $payloadJson === '') {
            return null;
        }

        $payload = json_decode($payloadJson, true);
        if (!is_array($payload)) {
            return null;
        }

        $sub = $payload['sub'] ?? null;

        return is_string($sub) && $sub !== '' ? $sub : null;
    }

    /**
     * @param list<string> $keys
     */
    public function resolvePayloadValue(Request $request, array $keys): ?string
    {
        $rawPayload = trim($request->getContent());
        if ($rawPayload === '') {
            return null;
        }

        $jsonValue = $this->resolveJsonPayloadValue($rawPayload, $keys);
        if ($jsonValue !== null) {
            return $jsonValue;
        }

        return $this->resolveFormPayloadValue($rawPayload, $keys);
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
        if (!is_array($formPayload)) {
            return null;
        }

        return $this->findStringValue($formPayload, $keys);
    }

    /**
     * @param array<string, array<int, string>|bool|float|int|string|null> $payload
     * @param list<string> $keys
     *
     * @return null|string
     */
    private function findStringValue(array $payload, array $keys): string|null|null
    {
        foreach ($keys as $key) {
            $value = $payload[$key] ?? null;
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function resolveClientIdFromBasicAuth(Request $request): string|null|null
    {
        $authorization = $request->headers->get('Authorization');
        if (!is_string($authorization) || !str_starts_with($authorization, 'Basic ')) {
            return null;
        }

        $encodedCredentials = trim(substr($authorization, 6));
        if ($encodedCredentials === '') {
            return null;
        }

        $decodedCredentials = base64_decode($encodedCredentials, true);
        if (!is_string($decodedCredentials) || $decodedCredentials === '') {
            return null;
        }

        $parts = explode(':', $decodedCredentials, 2);
        $clientId = $parts[0] ?? '';

        return $clientId !== '' ? $clientId : null;
    }

    private function resolveBearerToken(Request $request): ?string
    {
        $authorizationHeader = $request->headers->get('Authorization');
        if (
            is_string($authorizationHeader)
            && preg_match('/^Bearer\s+(\S+)/i', $authorizationHeader, $matches) === 1
        ) {
            return $matches[1];
        }

        $cookieToken = $request->cookies->get(self::AUTH_COOKIE_NAME);
        if (is_string($cookieToken) && $cookieToken !== '') {
            return $cookieToken;
        }

        return null;
    }

    private function decodeBase64Url(string $value): string|false
    {
        $paddedValue = str_pad(
            strtr($value, '-_', '+/'),
            strlen($value) + ((4 - strlen($value) % 4) % 4),
            '=',
            STR_PAD_RIGHT
        );

        return base64_decode($paddedValue, true);
    }
}
