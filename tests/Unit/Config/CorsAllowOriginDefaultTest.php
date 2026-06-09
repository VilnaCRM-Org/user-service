<?php

declare(strict_types=1);

namespace App\Tests\Unit\Config;

use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Yaml\Yaml;

/**
 * Guards the fail-closed CORS default that ships in the production image.
 *
 * `composer dump-env prod` (Dockerfile) bakes the committed .env values into the
 * production artifact. If .env shipped a working dev allow-list (localhost), the
 * prod service would reflect Access-Control-Allow-Origin for any localhost origin
 * together with Access-Control-Allow-Credentials: true (CWE-942). The committed
 * default must instead deny every origin until an operator sets a real allow-list.
 */
final class CorsAllowOriginDefaultTest extends UnitTestCase
{
    /**
     * A PCRE that, wrapped by nelmio as `{<value>}i`, matches no origin at all.
     * An empty value would compile to `{}i`, which matches EVERY origin, so the
     * fail-closed default must be a non-empty deny-all pattern.
     */
    private const DENY_ALL_REGEX = '(?!)';

    private const DEV_LOCALHOST_REGEX = '^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$';

    public function testCommittedEnvShipsFailClosedDenyAllDefault(): void
    {
        $value = $this->corsAllowOriginFrom('/.env');

        self::assertSame(
            self::DENY_ALL_REGEX,
            $value,
            'The committed .env (baked into the prod image) must default to the '
            . 'deny-all CORS regex, not a working localhost allow-list.'
        );
    }

    public function testCommittedEnvDefaultDoesNotAllowLocalhostOrAnyOrigin(): void
    {
        $value = $this->corsAllowOriginFrom('/.env');

        self::assertNotSame(
            '',
            $value,
            'An empty CORS_ALLOW_ORIGIN compiles to the regex "{}i" which matches '
            . 'every origin (fail-open); the default must be a deny-all pattern.'
        );

        $pattern = '{' . $value . '}i';

        foreach ([
            'http://localhost',
            'http://localhost:3000',
            'http://127.0.0.1',
            'http://127.0.0.1:8080',
            'https://app.vilnacrm.com',
            'http://evil.example.com',
        ] as $origin) {
            self::assertSame(
                0,
                preg_match($pattern, $origin),
                sprintf('Origin "%s" must be denied by the committed CORS default.', $origin)
            );
        }
    }

    public function testDevEnvKeepsLocalhostAllowList(): void
    {
        $value = $this->corsAllowOriginFrom('/.env.dev');

        self::assertSame(self::DEV_LOCALHOST_REGEX, $value);

        $pattern = '{' . $value . '}i';
        self::assertSame(1, preg_match($pattern, 'http://localhost:3000'));
        self::assertSame(1, preg_match($pattern, 'http://127.0.0.1'));
        self::assertSame(0, preg_match($pattern, 'http://evil.example.com'));
    }

    public function testTestEnvKeepsLocalhostAllowList(): void
    {
        $value = $this->corsAllowOriginFrom('/.env.test');

        self::assertSame(self::DEV_LOCALHOST_REGEX, $value);
    }

    public function testNelmioCorsBindsOriginToEnvVarInEveryEnvironment(): void
    {
        $config = Yaml::parseFile($this->projectDir() . '/config/packages/nelmio_cors.yaml');

        self::assertIsArray($config);

        foreach (['when@dev', 'when@test', 'when@prod'] as $envBlock) {
            $allowOrigin = $config[$envBlock]['nelmio_cors']['defaults']['allow_origin'] ?? null;

            self::assertSame(
                ['%env(CORS_ALLOW_ORIGIN)%'],
                $allowOrigin,
                sprintf('%s must read the origin allow-list from CORS_ALLOW_ORIGIN.', $envBlock)
            );
        }
    }

    private function corsAllowOriginFrom(string $relativeEnvPath): string
    {
        $path = $this->projectDir() . $relativeEnvPath;
        self::assertFileExists($path);

        $parsed = (new Dotenv())->parse((string) file_get_contents($path), $path);
        self::assertArrayHasKey(
            'CORS_ALLOW_ORIGIN',
            $parsed,
            sprintf('%s must define CORS_ALLOW_ORIGIN.', $relativeEnvPath)
        );

        return $parsed['CORS_ALLOW_ORIGIN'];
    }

    private function projectDir(): string
    {
        return dirname(__DIR__, 3);
    }
}
