<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Validator;

use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\SerializerInterface;

final readonly class AccessTokenValidator
{
    private const JWT_ALGORITHM = 'RS256';
    private const JWT_HEADER_DEPTH_LIMIT = 4;

    public function __construct(
        private SerializerInterface $serializer,
        private JWTEncoderInterface $jwtEncoder,
        private string $jwtIssuer = 'vilnacrm-user-service',
        private string $jwtAudience = 'vilnacrm-api',
    ) {
    }

    /**
     * @psalm-return array{subject: non-empty-string, sid: string, roles: non-empty-list<non-empty-string>}
     */
    public function validate(string $token): array
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
        if (count($parts) !== 3) {
            throw new CustomUserMessageAuthenticationException('Invalid access token.');
        }

        return $this->decodeJsonObject(
            $this->decodeBase64Url($parts[0])
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
        $now = time();
        $notBefore = $this->extractTimestamp($payload, 'nbf');
        $expiresAt = $this->extractTimestamp($payload, 'exp');

        if ($notBefore > $now || $expiresAt <= $now) {
            throw new CustomUserMessageAuthenticationException('Invalid access token claims.');
        }

        $this->validateFirstPartyClaims($payload);
    }

    /**
     * @param array<string, array<int, string>|bool|float|int|string|null> $payload
     */
    private function validateFirstPartyClaims(array $payload): void
    {
        if (isset($payload['roles'])) {
            $this->validateIssuerAndAudience($payload);

            if (!isset($payload['sid'])) {
                throw new CustomUserMessageAuthenticationException('Invalid access token claims.');
            }
        } elseif (($payload['iss'] ?? null) !== null) {
            $this->validateIssuerAndAudience($payload);
        }
    }

    /**
     * @param array<string, array<int, string>|bool|float|int|string|null> $payload
     */
    private function validateIssuerAndAudience(array $payload): void
    {
        $issuer = $payload['iss'] ?? null;
        if (!is_string($issuer) || $issuer !== $this->jwtIssuer) {
            throw new CustomUserMessageAuthenticationException('Invalid access token claims.');
        }

        if (!$this->hasExpectedAudience($payload)) {
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
            return $audience === $this->jwtAudience;
        }

        if (!is_array($audience) || $audience === []) {
            return false;
        }

        $strings = array_filter($audience, 'is_string');
        $nonEmpty = array_filter($strings);
        if (count($nonEmpty) !== count($audience)) {
            return false;
        }

        return in_array($this->jwtAudience, $audience, true);
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
        foreach (['sub', 'client_id'] as $key) {
            $value = $payload[$key] ?? null;
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        throw new CustomUserMessageAuthenticationException('Invalid access token claims.');
    }

    /**
     * @param array<string, array<int, string>|bool|float|int|string|null> $payload
     */
    private function extractSid(array $payload): string
    {
        $sid = $payload['sid'] ?? null;
        if ($sid === null) {
            return '';
        }

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
        if ($rawRoles === null) {
            return isset($payload['iss']) ? ['ROLE_USER'] : ['ROLE_SERVICE'];
        }

        if (!is_array($rawRoles) || $rawRoles === []) {
            throw new CustomUserMessageAuthenticationException('Invalid access token claims.');
        }

        $this->assertAllRolesAreNonEmptyStrings($rawRoles);

        return array_values(array_unique($rawRoles));
    }

    /**
     * @param array<int, string|int|bool|float|null> $rawRoles
     *
     * @psalm-assert non-empty-list<non-empty-string> $rawRoles
     */
    private function assertAllRolesAreNonEmptyStrings(array $rawRoles): void
    {
        $strings = array_filter($rawRoles, 'is_string');
        $nonEmpty = array_filter($strings);
        if (count($nonEmpty) !== count($rawRoles)) {
            throw new CustomUserMessageAuthenticationException('Invalid access token claims.');
        }
    }

    /**
     * @return array<string, array<int, string>|bool|float|int|string|null>
     */
    private function decodeJsonObject(string $json): array
    {
        try {
            $decodedObject = $this->serializer->decode(
                $json,
                JsonEncoder::FORMAT,
                [
                    JsonDecode::ASSOCIATIVE => true,
                    JsonDecode::OPTIONS => JSON_THROW_ON_ERROR,
                    JsonDecode::RECURSION_DEPTH => self::JWT_HEADER_DEPTH_LIMIT,
                ],
            );
        } catch (NotEncodableValueException) {
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
