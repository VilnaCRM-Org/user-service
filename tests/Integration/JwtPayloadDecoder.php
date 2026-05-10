<?php

declare(strict_types=1);

namespace App\Tests\Integration;

final class JwtPayloadDecoder
{
    /**
     * @return array<string, array<string>|int|string>
     */
    public static function decode(string $jwt): array
    {
        $parts = explode('.', $jwt);
        $payload = $parts[1] ?? '';
        $pad = (4 - strlen($payload) % 4) % 4;
        $raw = base64_decode(
            strtr($payload, '-_', '+/') . str_repeat('=', $pad),
            true
        );
        $decoded = json_decode((string) $raw, true);

        return is_array($decoded) ? $decoded : [];
    }
}
