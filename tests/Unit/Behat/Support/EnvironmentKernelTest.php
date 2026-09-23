<?php

declare(strict_types=1);

namespace App\Tests\Unit\Behat\Support;

use App\Tests\Behat\Support\EnvironmentKernel;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class EnvironmentKernelTest extends UnitTestCase
{
    public function testBuildAndCacheDirectoriesAreTheSameIsolatedPath(): void
    {
        $kernel = new EnvironmentKernel('prod', false, dirname(__DIR__, 4));

        self::assertSame($kernel->getCacheDir(), $kernel->getBuildDir());
        self::assertStringContainsString('var/cache/behat-environment/prod', $kernel->getCacheDir());
    }

    public function testOnlyMongoTransportOptionsAreOverriddenForBehat(): void
    {
        $dsn = 'mongodb://database:27017';
        $driverOptions = ['context' => ['ssl' => ['verify_peer' => true]]];
        $options = [
            'tls' => true,
            'tlsCAFile' => '/test/documentdb-ca.pem',
            'retryWrites' => false,
        ];
        $container = new ContainerBuilder();
        $container->setDefinition(
            'doctrine_mongodb.odm.default_connection',
            new Definition('MongoDB\\Client', [$dsn, $options, $driverOptions])
        );

        (new EnvironmentKernel('prod', false, dirname(__DIR__, 4)))->process($container);

        $arguments = $container
            ->getDefinition('doctrine_mongodb.odm.default_connection')
            ->getArguments();

        self::assertSame($dsn, $arguments[0]);
        self::assertSame($driverOptions, $arguments[2]);
        self::assertSame(false, $arguments[1]['tls']);
        self::assertFalse(isset($arguments[1]['tlsCAFile']));
        self::assertFalse($arguments[1]['retryWrites']);
    }
}
