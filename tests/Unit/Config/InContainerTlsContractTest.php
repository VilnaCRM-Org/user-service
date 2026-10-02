<?php

declare(strict_types=1);

namespace App\Tests\Unit\Config;

use App\Tests\Unit\UnitTestCase;

final class InContainerTlsContractTest extends UnitTestCase
{
    private const CADDYFILE = 'infrastructure/docker/caddy/Caddyfile.prod';

    private const HTTP_PORT = 8080;

    private const TLS_PORT = 8443;

    private const SERVER_NAME = 'user-service.internal';

    private const STORAGE = '/srv/app/var/caddy';

    private const APPLICATION_VOLUME = '/srv/app/var';

    private const IMPORT = 'import application';

    private const TRUSTED_PROXIES = [
        'trusted_proxies static {$TRUSTED_PROXY_CIDRS:127.0.0.1/32}',
        'trusted_proxies_strict',
    ];

    public function testEveryListenerServesTheSameApplication(): void
    {
        self::assertSame(self::IMPORT, $this->block(sprintf(':%d', self::HTTP_PORT)));
        self::assertSame(self::IMPORT, $this->block(sprintf('https://:%d', self::TLS_PORT)));
        self::assertStringEndsWith(self::IMPORT, $this->block($this->namedSite()));
        self::assertStringContainsString('php_server {', $this->block('(application)'));
        self::assertStringContainsString('replace code REDACTED', $this->block('(application)'));
    }

    public function testTlsPortUsesOnlyTheInternalIssuer(): void
    {
        $issuer = "tls {\n\t\tissuer internal {\n\t\t\tlifetime "
            . '{$CADDY_INTERNAL_CERT_LIFETIME:12h}' . "\n\t\t}\n\t}";

        self::assertStringStartsWith($issuer, $this->block($this->namedSite()));
        self::assertSame(1, substr_count($this->caddyfile(), 'tls {'));
        self::assertDoesNotMatchRegularExpression(
            '/acme|zerossl|load_files|get_certificate|\.(crt|key|pem)\b/i',
            $this->caddyfile()
        );
        self::assertStringContainsString("\tskip_install_trust\n", $this->globalOptions());
        self::assertStringContainsString("\tlocal_certs\n", $this->globalOptions());
    }

    public function testCertificateStorageIsCreatedAtRuntimeInTheApplicationVolume(): void
    {
        $dockerfile = $this->projectFile('Dockerfile');

        self::assertStringContainsString(
            sprintf("\tstorage file_system %s\n", self::STORAGE),
            $this->globalOptions()
        );
        self::assertStringStartsWith(self::APPLICATION_VOLUME . '/', self::STORAGE);
        self::assertStringContainsString(
            sprintf('VOLUME ["%s",', self::APPLICATION_VOLUME),
            $this->webStage()
        );
        self::assertStringNotContainsString(self::STORAGE, $dockerfile);
        self::assertDoesNotMatchRegularExpression('/caddy trust|\.key\b/', $dockerfile);
    }

    public function testTlsPortServesNoPlainHttpFallbackOrRedirect(): void
    {
        self::assertStringContainsString(
            "\tauto_https disable_redirects\n",
            $this->globalOptions()
        );
        self::assertDoesNotMatchRegularExpression(
            '/auto_https off|http_redirect|\bredir\b|^http:\/\//m',
            $this->caddyfile()
        );
    }

    public function testClientsWithoutOrWithAnotherSniGetTheInternalCertificate(): void
    {
        $global = $this->globalOptions();

        self::assertSame(
            sprintf('https://%s:%d', self::SERVER_NAME, self::TLS_PORT),
            $this->namedSite()
        );
        self::assertStringContainsString("\tdefault_sni " . self::SERVER_NAME . "\n", $global);
        self::assertStringContainsString("\tfallback_sni " . self::SERVER_NAME . "\n", $global);
    }

    public function testTlsServerKeepsTrustedProxiesAndServesOnlyTcpProtocols(): void
    {
        $tlsServer = $this->nestedBlock(sprintf('servers :%d', self::TLS_PORT));
        $defaultServers = $this->nestedBlock('servers');

        self::assertSame(implode("\n", self::TRUSTED_PROXIES), $defaultServers);
        self::assertSame(
            implode("\n", [...self::TRUSTED_PROXIES, 'protocols h1 h2']),
            $tlsServer
        );
    }

    public function testRenewalKeepsCaddyDefaultsUnlessATestShortensThem(): void
    {
        self::assertStringContainsString(
            "\trenew_interval {\$CADDY_RENEW_INTERVAL:10m}\n",
            $this->globalOptions()
        );
    }

    public function testOnlyTheWebImageExposesTheTlsPort(): void
    {
        self::assertMatchesRegularExpression(
            sprintf('/^EXPOSE %d %d$/m', self::HTTP_PORT, self::TLS_PORT),
            $this->webStage()
        );
        self::assertDoesNotMatchRegularExpression('/^EXPOSE /m', $this->stage('app_workers'));
        self::assertStringNotContainsString('Caddyfile.prod', $this->stage('app_workers'));
    }

    private function namedSite(): string
    {
        self::assertSame(
            1,
            preg_match('/^(https:\/\/[a-z][a-z0-9.-]+:\d+) \{$/m', $this->caddyfile(), $match)
        );

        return $match[1];
    }

    private function block(string $address): string
    {
        $pattern = '/^' . preg_quote($address, '/') . ' \{\n(.*?)\n\}$/ms';
        self::assertSame(
            1,
            preg_match($pattern, $this->caddyfile(), $match),
            sprintf('Expected one %s block in %s.', $address, self::CADDYFILE)
        );

        return trim($match[1]);
    }

    private function nestedBlock(string $name): string
    {
        $pattern = '/^\t' . preg_quote($name, '/') . ' \{\n(.*?)\n\t\}$/ms';
        self::assertSame(1, preg_match($pattern, $this->globalOptions(), $match));

        return implode("\n", array_map('trim', explode("\n", $match[1])));
    }

    private function globalOptions(): string
    {
        self::assertSame(1, preg_match('/\A\{\n(.*?\n)\}$/ms', $this->caddyfile(), $match));

        return $match[1];
    }

    private function webStage(): string
    {
        return $this->stage('frankenphp_prod');
    }

    private function stage(string $stage): string
    {
        $pattern = '/^FROM \S+ AS ' . preg_quote($stage, '/') . '$(.*?)(?=^FROM |\z)/ms';
        self::assertSame(1, preg_match($pattern, $this->projectFile('Dockerfile'), $match));

        return $match[1];
    }

    private function caddyfile(): string
    {
        return $this->projectFile(self::CADDYFILE);
    }

    private function projectFile(string $path): string
    {
        $contents = file_get_contents(dirname(__DIR__, 3) . '/' . $path);
        self::assertIsString($contents);

        return $contents;
    }
}
