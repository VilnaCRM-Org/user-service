<?php

declare(strict_types=1);

namespace App\Tests\Integration\Auth;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Serializer\Encoder\JsonDecode;

final class ProductionConfigValidationTest extends AuthIntegrationTestCase
{
    /**
     * AC: NFR-17 - MongoDB production DSN must enable TLS
     */
    public function testProductionMongodbDsnRuntimeConfiguration(): void
    {
        $mongodbUrl = $this->resolveMongodbUrl();
        $this->assertNotSame('', $mongodbUrl, 'MONGODB_URL must be defined at runtime.');
        $this->assertValidMongodbScheme($mongodbUrl);
        if (!$this->isProductionEnvironment()) {
            $this->assertNotSame('prod', $this->container->getParameter('kernel.environment'));

            return;
        }
        $this->assertTlsEnabled($mongodbUrl);
    }

    /**
     * AC: NFR-18 - Document TLS 1.2+ and HSTS requirements for production
     */
    public function testRuntimeSecurityHeadersIncludeTransportHardeningControls(): void
    {
        $response = $this->sendHealthCheckRequest();
        $this->assertSecurityHeaders($response);
    }

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
        self::assertSame('filter', $config['logging']['logs']['log0']['encoder']['format']);
        self::assertSame('json', $config['logging']['logs']['log0']['encoder']['wrap']['format']);
        self::assertSame([
            ['parameter' => 'code', 'type' => 'replace', 'value' => 'REDACTED'],
            ['parameter' => 'state', 'type' => 'replace', 'value' => 'REDACTED'],
        ], $config['logging']['logs']['log0']['encoder']['fields']['request>uri']['actions']);
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

    private function resolveMongodbUrl(): string
    {
        $envValue = getenv('MONGODB_URL');

        return $envValue !== false ? $envValue : '';
    }

    private function isProductionEnvironment(): bool
    {
        return $this->container->getParameter('kernel.environment') === 'prod';
    }

    private function assertValidMongodbScheme(string $url): void
    {
        $this->assertContains(
            parse_url($url, PHP_URL_SCHEME),
            ['mongodb', 'mongodb+srv'],
            'MongoDB DSN must use mongodb:// or mongodb+srv:// scheme.'
        );
    }

    private function assertTlsEnabled(string $mongodbUrl): void
    {
        $query = parse_url($mongodbUrl, PHP_URL_QUERY);
        parse_str(is_string($query) ? $query : '', $queryParameters);
        $tlsEnabled = filter_var(
            $queryParameters['tls'] ?? null,
            FILTER_VALIDATE_BOOL,
            FILTER_NULL_ON_FAILURE
        ) === true;
        $this->assertTrue(
            $tlsEnabled,
            'Production MongoDB connection string must include tls=true parameter (AC: NFR-17).'
        );
    }

    private function sendHealthCheckRequest(): Response
    {
        $kernel = self::getContainer()->get('kernel');
        $this->assertInstanceOf(HttpKernelInterface::class, $kernel);

        return $kernel->handle(
            Request::create(
                '/api/health',
                Request::METHOD_GET,
                [],
                [],
                [],
                ['HTTP_ACCEPT' => 'application/json']
            )
        );
    }

    private function assertSecurityHeaders(Response $response): void
    {
        $headers = $response->headers;
        $this->assertSame(
            'max-age=31536000; includeSubDomains',
            $headers->get('Strict-Transport-Security')
        );
        $this->assertSame('nosniff', $headers->get('X-Content-Type-Options'));
        $this->assertSame('DENY', $headers->get('X-Frame-Options'));
        $this->assertSame(
            'strict-origin-when-cross-origin',
            $headers->get('Referrer-Policy')
        );
        $this->assertSame(
            "default-src 'none'; frame-ancestors 'none'",
            $headers->get('Content-Security-Policy')
        );
        $this->assertSame(
            'camera=(), microphone=(), geolocation=(), payment=(), usb=()',
            $headers->get('Permissions-Policy')
        );
    }
}
