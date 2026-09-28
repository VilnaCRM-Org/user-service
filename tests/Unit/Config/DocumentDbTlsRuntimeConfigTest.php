<?php

declare(strict_types=1);

namespace App\Tests\Unit\Config;

use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Yaml\Yaml;

final class DocumentDbTlsRuntimeConfigTest extends UnitTestCase
{
    public function testConnectionUsesUriOptionsWithoutOverridingDocumentDbOrLocalMongo(): void
    {
        $config = Yaml::parseFile(
            dirname(__DIR__, 3) . '/config/packages/doctrine_mongodb.yaml'
        );

        $connection = $config['doctrine_mongodb']['connections']['default'];

        self::assertSame('%env(MONGODB_URL)%', $connection['server']);
        self::assertSame([], $connection['options']);
        self::assertTrue(
            $config['doctrine_mongodb']['document_managers']['default']['auto_mapping']
        );
        self::assertArrayNotHasKey('when@prod', $config);
    }

}
