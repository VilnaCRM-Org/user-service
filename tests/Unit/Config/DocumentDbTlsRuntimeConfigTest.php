<?php

declare(strict_types=1);

namespace App\Tests\Unit\Config;

use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Yaml\Yaml;

final class DocumentDbTlsRuntimeConfigTest extends UnitTestCase
{
    private const CA_BUNDLE_PATH = '/usr/local/share/ca-certificates/aws-documentdb-global-bundle.pem';

    public function testProductionMongoDbConnectionUsesTheImageCaBundle(): void
    {
        $config = Yaml::parseFile(
            dirname(__DIR__, 3) . '/config/packages/doctrine_mongodb.yaml'
        );

        self::assertSame([], $config['doctrine_mongodb']['connections']['default']['options']);
        self::assertTrue(
            $config['doctrine_mongodb']['document_managers']['default']['auto_mapping']
        );
        self::assertTrue(
            $config['when@prod']['doctrine_mongodb']['connections']['default']['options']['tls']
        );
        self::assertSame(
            self::CA_BUNDLE_PATH,
            $config['when@prod']['doctrine_mongodb']['connections']['default']['options']['tlsCAFile']
        );
        self::assertArrayNotHasKey('document_managers', $config['when@prod']['doctrine_mongodb']);
    }
}
