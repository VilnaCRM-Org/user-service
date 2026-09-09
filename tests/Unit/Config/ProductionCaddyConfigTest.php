<?php

declare(strict_types=1);

namespace App\Tests\Unit\Config;

use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Process\Process;
use Symfony\Component\Serializer\Encoder\JsonDecode;

final class ProductionCaddyConfigTest extends UnitTestCase
{
    public function testProductionIgnoresDevelopmentListenerAndEnvironmentOverrides(): void
    {
        $config = $this->adapt([
            'SERVER_NAME' => $this->faker->domainName(),
            'CADDY_GLOBAL_OPTIONS' => 'http_port 8081',
            'CADDY_EXTRA_CONFIG' => 'respond "development"',
            'CADDY_DEBUG' => 'debug',
            'FRANKENPHP_CONFIG' => 'import missing-development-worker.Caddyfile',
            'CADDY_PHP_APP_ENV' => 'test',
            'CADDY_PHP_APP_DEBUG' => '1',
            'APP_ENV' => 'test',
            'APP_DEBUG' => '1',
        ]);
        $servers = $config['apps']['http']['servers'];

        self::assertCount(1, $servers);
        self::assertSame([':80'], $servers['srv0']['listen']);
        self::assertTrue($servers['srv0']['automatic_https']['disable']);
        self::assertSame('localhost:2019', $config['admin']['listen']);
        self::assertArrayNotHasKey('tls', $config['apps']);
        self::assertSame('prod', $config['apps']['frankenphp']['workers'][0]['env']['APP_ENV']);
        self::assertSame('0', $config['apps']['frankenphp']['workers'][0]['env']['APP_DEBUG']);
    }

    public function testProductionWorkerKeepsProductionSettings(): void
    {
        $config = $this->adapt();
        $workers = $config['apps']['frankenphp']['workers'];

        self::assertCount(1, $workers);
        self::assertSame('/srv/app/public/index.php', $workers[0]['file_name']);
        self::assertSame([
            'APP_DEBUG' => '0',
            'APP_ENV' => 'prod',
            'APP_RUNTIME' => 'Runtime\\FrankenPhpSymfony\\Runtime',
        ], $workers[0]['env']);
    }

    public function testProductionRequestHandlerKeepsProductionSettingsAndLogging(): void
    {
        $config = $this->adapt();
        $routes = $config['apps']['http']['servers']['srv0']['routes'];
        $handlers = array_merge(...array_column($routes, 'handle'));
        $php = array_values(array_filter(
            $handlers,
            static fn (array $handler): bool => $handler['handler'] === 'php'
        ));
        $headers = array_values(array_filter(
            $handlers,
            static fn (array $handler): bool => $handler['handler'] === 'headers'
        ));

        self::assertCount(1, $php);
        self::assertSame(['APP_DEBUG' => '0', 'APP_ENV' => 'prod'], $php[0]['env']);
        self::assertSame(['Server'], $headers[0]['response']['delete']);
        self::assertArrayNotHasKey('request', $headers[0]);
        self::assertSame('stderr', $config['logging']['logs']['log0']['writer']['output']);
        self::assertSame('json', $config['logging']['logs']['log0']['encoder']['format']);
    }

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

    /**
     * @param array<string, string> $environment
     *
     * @return array<string, array<string, array<array-key, array>|string>>
     */
    private function adapt(array $environment = []): array
    {
        $process = new Process([
            'frankenphp',
            'adapt',
            '--adapter',
            'caddyfile',
            '--config',
            dirname(__DIR__, 3) . '/infrastructure/docker/caddy/Caddyfile.prod',
        ], null, $environment, null, 10);
        $process->mustRun();

        return (new JsonDecode())->decode(
            $process->getOutput(),
            'json',
            [JsonDecode::ASSOCIATIVE => true]
        );
    }
}
