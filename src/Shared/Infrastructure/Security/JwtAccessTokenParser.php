<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Security;

use JsonException;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

final readonly class JwtAccessTokenParser
{
    private const JWT_ISSUER = 'vilnacrm-user-service';
    private const JWT_AUDIENCE = 'vilnacrm-api';
    private const JWT_ALGORITHM = 'RS256';

    public function __construct(private JWTEncoderInterface $jwtEncoder)
    {
    }

    /**
     * @psalm-return array{subject: non-empty-string, sid: non-empty-string, roles: non-empty-list<non-empty-string>}
     */
    public function parse(string $token): array
    {
        $header = $this->decodeHeader($token);
        $this->validateAlgorithm($header);

        $payload = $this->decodePayload($token);
        $this->validateClaims($payload);

        return [
            'subject' => $this->extractSubject($payload),
            'sid' => $this->extractSid($payload),
            'roles' => $this->extractRoles($payload),
        ];
    }

    /**
     * @return array<string, array<int, string>|bool|float|int|string|null>
     */
    private function decodeHeader(string $token): array
    {
        $parts = explode('.', $token);
        $headerPart = $parts[0] ?? null;
        if (count($parts) !== 3 || !is_string($headerPart) || $headerPart === '') {
            throw new CustomUserMessageAuthenticationException('Invalid access token.');
        }

        return $this->decodeJsonObject(
            $this->decodeBase64Url($headerPart)
        );
    }

    /**
     * @return array<string, array<int, string>|bool|float|int|string|null>
     */
    private function decodePayload(string $token): array
    {
        try {
            $payload = $this->jwtEncoder->decode($token);
        } catch (JWTDecodeFailureException) {
            throw new CustomUserMessageAuthenticationException('Invalid access token.');
        }

        if (!is_array($payload)) {
            throw new CustomUserMessageAuthenticationException('Invalid access token.');
        }

        return $payload;
    }

    /**
     * @param array<string, array<int, string>|bool|float|int|string|null> $header
     */
    private function validateAlgorithm(array $header): void
    {
        $algorithm = $header['alg'] ?? null;
        if (!is_string($algorithm) || $algorithm !== self::JWT_ALGORITHM) {
            throw new CustomUserMessageAuthenticationException('Invalid access token.');
        }
    }

    /**
     * @param array<string, array<int, string>|bool|float|int|string|null> $payload
     */
    private function validateClaims(array $payload): void
    {
        $issuer = $payload['iss'] ?? null;
        if (!is_string($issuer) || $issuer !== self::JWT_ISSUER) {
            throw new CustomUserMessageAuthenticationException('Invalid access token claims.');
        }

        if (!$this->hasExpectedAudience($payload)) {
            throw new CustomUserMessageAuthenticationException('Invalid access token claims.');
        }

        $now = time();
        $notBefore = $this->extractTimestamp($payload, 'nbf');
        $expiresAt = $this->extractTimestamp($payload, 'exp');

        if ($notBefore > $now || $expiresAt <= $now) {
            throw new CustomUserMessageAuthenticationException('Invalid access token claims.');
        }
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

        if (!is_array($audience) || $audience === []) {
            return false;
        }

        $hasExpectedAudience = false;
        foreach ($audience as $value) {
            if (!is_string($value) || $value === '') {
                return false;
            }

            if ($value === self::JWT_AUDIENCE) {
                $hasExpectedAudience = true;
            }
        }

        return $hasExpectedAudience;
    }

    /**
     * @param array<string, array<int, string>|bool|float|int|string|null> $payload
     */
    private function extractTimestamp(array $payload, string $field): int
    {
        $value = $payload[$field] ?? null;
        if (!is_int($value)) {
            throw new CustomUserMessageAuthenticationException('Invalid access token claims.');
        }

        return $value;
    }

    /**
     * @param array<string, array<int, string>|bool|float|int|string|null> $payload
     */
    private function extractSubject(array $payload): string
    {
        $subject = $payload['sub'] ?? null;
        if (!is_string($subject) || $subject === '') {
            throw new CustomUserMessageAuthenticationException('Invalid access token claims.');
        }

        return $subject;
    }

    /**
     * @param array<string, array<int, string>|bool|float|int|string|null> $payload
     */
    private function extractSid(array $payload): string
    {
        $sid = $payload['sid'] ?? null;
        if (!is_string($sid) || $sid === '') {
            throw new CustomUserMessageAuthenticationException('Invalid access token claims.');
        }

        return $sid;
    }

    /**
     * @param array<string, array<int, string>|bool|float|int|string|null> $payload
     *
     * @return array<string>
     *
     * @psalm-return non-empty-list<non-empty-string>
     */
    private function extractRoles(array $payload): array
    {
        $rawRoles = $payload['roles'] ?? null;
        if (!is_array($rawRoles) || $rawRoles === []) {
            throw new CustomUserMessageAuthenticationException('Invalid access token claims.');
        }

        $roles = [];
        foreach ($rawRoles as $role) {
            if (!is_string($role) || $role === '') {
                throw new CustomUserMessageAuthenticationException('Invalid access token claims.');
            }

            $roles[] = $role;
        }

        return array_values(array_unique($roles));
    }

    /**
     * @return array<string, array<int, string>|bool|float|int|string|null>
     */
    private function decodeJsonObject(string $json): array
    {
        try {
            $decodedObject = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new CustomUserMessageAuthenticationException('Invalid access token.');
        }

        if (!is_array($decodedObject)) {
            throw new CustomUserMessageAuthenticationException('Invalid access token.');
        }

        return $decodedObject;
    }

    private function decodeBase64Url(string $value): string
    {
        $decoded = base64_decode(strtr($value, '-_', '+/'), true);
        if (!is_string($decoded) || $decoded === '') {
            throw new CustomUserMessageAuthenticationException('Invalid access token.');
        }

        return $decoded;
    }
}
