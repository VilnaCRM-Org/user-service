<?php

declare(strict_types=1);

namespace App\Tests\Unit\Config;

use App\Tests\Unit\UnitTestCase;

final class ProductionCaddyConfigTest extends UnitTestCase
{
    public function testOnlyProductionImageSelectsTheProductionConfig(): void
    {
        $dockerfile = (string) file_get_contents(dirname(__DIR__, 3) . '/Dockerfile');
        [$development, $production] = explode(
            'FROM frankenphp_base AS frankenphp_prod',
            $dockerfile
        );
        [$production, $worker] = explode('FROM frankenphp_base AS app_workers', $production);
        $copy = 'COPY --link infrastructure/docker/caddy/Caddyfile.prod /etc/caddy/Caddyfile';

        self::assertStringContainsString($copy, $production);
        self::assertStringNotContainsString($copy, $development);
        self::assertStringNotContainsString($copy, $worker);
        self::assertStringContainsString(
            'COPY --link infrastructure/docker/caddy/Caddyfile /etc/caddy/Caddyfile',
            $development
        );
    }

    public function testProductionCaddyAndSymfonyKeepClosedProxyDefaults(): void
    {
        $caddyfile = (string) file_get_contents(
            dirname(__DIR__, 3) . '/infrastructure/docker/caddy/Caddyfile.prod'
        );
        $environment = (string) file_get_contents(dirname(__DIR__, 3) . '/.env');
        $framework = (string) file_get_contents(
            dirname(__DIR__, 3) . '/config/packages/framework.yaml'
        );

        self::assertStringContainsString(
            'trusted_proxies static {$TRUSTED_PROXY_CIDRS:127.0.0.1/32}',
            $caddyfile
        );
        self::assertStringContainsString('TRUSTED_PROXIES=127.0.0.1/32', $environment);
        self::assertStringContainsString('TRUSTED_PROXY_CIDRS=127.0.0.1/32', $environment);
        self::assertStringContainsString(
            "trusted_proxies: '%env(TRUSTED_PROXIES)%'",
            $framework
        );
    }
}
