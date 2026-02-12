<?php

declare(strict_types=1);

namespace App\Tests\Integration\Auth;

use App\Tests\Integration\IntegrationTestCase;

final class IntegrationAuthTokenHelperTest extends IntegrationTestCase
{
    public function testCreateBearerTokenForUserIncludesExpectedClaims(): void
    {
        $userId = $this->faker->uuid();

        $token = $this->createBearerTokenForUser($userId);
        $payload = $this->decodeJwtPayload($token);

        $this->assertSame($userId, $payload['sub'] ?? null);
        $this->assertSame('vilnacrm-user-service', $payload['iss'] ?? null);
        $this->assertSame('vilnacrm-api', $payload['aud'] ?? null);
        $this->assertSame(['ROLE_USER'], $payload['roles'] ?? null);
        $this->assertIsString($payload['sid'] ?? null);
        $this->assertNotSame('', (string) ($payload['sid'] ?? ''));
    }

    public function testCreateBearerTokenForRoleSupportsServiceRole(): void
    {
        $token = $this->createBearerTokenForRole('ROLE_SERVICE');
        $payload = $this->decodeJwtPayload($token);

        $this->assertSame(['ROLE_SERVICE'], $payload['roles'] ?? null);
    }

    public function testCreateAuthenticatedHeadersReturnsBearerAuthorization(): void
    {
        $subject = sprintf('subject-%s', strtolower($this->faker->lexify('????')));

        $headers = $this->createAuthenticatedHeaders($subject, ['ROLE_SERVICE']);

        $this->assertArrayHasKey('HTTP_AUTHORIZATION', $headers);
        $this->assertArrayHasKey('HTTP_ACCEPT', $headers);
        $this->assertSame('application/json', $headers['HTTP_ACCEPT']);
        $this->assertStringStartsWith('Bearer ', $headers['HTTP_AUTHORIZATION']);

        $token = substr($headers['HTTP_AUTHORIZATION'], 7);
        $payload = $this->decodeJwtPayload($token);

        $this->assertSame($subject, $payload['sub'] ?? null);
        $this->assertSame(['ROLE_SERVICE'], $payload['roles'] ?? null);
    }

    /**
     * @return array<string, array<string>|int|string>
     */
    private function decodeJwtPayload(string $jwt): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return [];
        }

        $payload = $this->base64UrlDecode($parts[1]);
        if ($payload === '') {
            return [];
        }

        $decoded = json_decode($payload, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function base64UrlDecode(string $value): string
    {
        $remainder = strlen($value) % 4;
        if ($remainder !== 0) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        return is_string($decoded) ? $decoded : '';
    }
}
