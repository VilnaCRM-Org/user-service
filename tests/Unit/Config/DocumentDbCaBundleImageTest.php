<?php

declare(strict_types=1);

namespace App\Tests\Unit\Config;

use App\Tests\Unit\UnitTestCase;

final class DocumentDbCaBundleImageTest extends UnitTestCase
{
    private const BUNDLE_PATH = '/usr/local/share/ca-certificates/aws-documentdb-global-bundle.pem';

    private const BUNDLE_SHA256 =
        'e5bb2084ccf45087bda1c9bffdea0eb15ee67f0b91646106e466714f9de3c7e3';

    private const BUNDLE_URL = 'https://truststore.pki.rds.amazonaws.com/global/global-bundle.pem';

    public function testSharedProductionBaseInstallsTheVerifiedAwsDocumentDbBundle(): void
    {
        $dockerfile = $this->dockerfile();
        [$base, $targets] = explode('FROM frankenphp_base AS frankenphp_dev', $dockerfile);

        self::assertStringContainsString('ARG DOCUMENTDB_CA_BUNDLE_URL=' . self::BUNDLE_URL, $base);
        self::assertStringContainsString(
            'ARG DOCUMENTDB_CA_BUNDLE_SHA256=' . self::BUNDLE_SHA256,
            $base
        );
        self::assertStringContainsString(
            'ARG DOCUMENTDB_CA_BUNDLE_PATH=' . self::BUNDLE_PATH,
            $base
        );
        self::assertStringContainsString('sha256sum -c -', $base);
        self::assertStringContainsString('openssl_x509_read', $base);
        self::assertStringContainsString('chmod 0644 "${DOCUMENTDB_CA_BUNDLE_PATH}"', $base);
        self::assertStringContainsString('FROM frankenphp_base AS frankenphp_prod', $targets);
        self::assertStringContainsString('FROM frankenphp_base AS app_workers', $targets);
    }

    private function dockerfile(): string
    {
        $dockerfile = file_get_contents(dirname(__DIR__, 3) . '/Dockerfile');

        self::assertIsString($dockerfile);

        return $dockerfile;
    }
}
