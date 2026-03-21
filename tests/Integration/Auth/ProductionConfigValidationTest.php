<?php

declare(strict_types=1);

namespace App\Tests\Integration\Auth;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

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
