<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi;

use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Yaml\Yaml;

final class PublicPasskeySecuritySpecTest extends UnitTestCase
{
    private const PUBLIC_PASSKEY_PATHS = [
        '/api/passkeys/signup/options',
        '/api/passkeys/signup/complete',
        '/api/passkeys/signin/options',
        '/api/passkeys/signin/complete',
    ];

    private const AUTHENTICATED_PASSKEY_PATHS = [
        '/api/passkeys/register/options',
        '/api/passkeys/register/complete',
    ];

    public function testPublicPasskeyRestOperationsOverrideGlobalOauthRequirement(): void
    {
        foreach (self::PUBLIC_PASSKEY_PATHS as $path) {
            self::assertSame(
                [],
                $this->openApiPostOperation($path)['security'] ?? null,
                sprintf('%s must stay public in the generated OpenAPI spec.', $path)
            );
        }
    }

    public function testAuthenticatedPasskeyRegistrationOperationsKeepGlobalOauthRequirement(): void
    {
        foreach (self::AUTHENTICATED_PASSKEY_PATHS as $path) {
            self::assertArrayNotHasKey(
                'security',
                $this->openApiPostOperation($path),
                sprintf('%s must inherit the global OAuth requirement.', $path)
            );
        }
    }

    /**
     * @return array<string, array|bool|float|int|string|null>
     */
    private function openApiPostOperation(string $path): array
    {
        $spec = Yaml::parseFile(dirname(__DIR__, 5) . '/.github/openapi-spec/spec.yaml');

        self::assertIsArray($spec);
        self::assertIsArray($spec['paths'] ?? null);
        self::assertIsArray($spec['paths'][$path] ?? null);
        self::assertIsArray($spec['paths'][$path]['post'] ?? null);

        return $spec['paths'][$path]['post'];
    }
}
