<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Converter;

/**
 * Unpadded base64url (RFC 7515 section 2) used by JWS segments and JWK values.
 */
final readonly class Base64UrlConverter
{
    public function encode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    public function decode(string $value): ?string
    {
        if (preg_match('/^[A-Za-z0-9_-]+$/D', $value) !== 1) {
            return null;
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        return $decoded === false ? null : $decoded;
    }
}
