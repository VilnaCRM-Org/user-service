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
        $kernel = new EnvironmentKernel(
            'prod',
            false,
            dirname(__DIR__, 4),
            'mongodb://test-server'
        );

        self::assertSame($kernel->getCacheDir(), $kernel->getBuildDir());
        self::assertStringContainsString(
            'var/cache/behat-environment/prod',
            $kernel->getCacheDir()
        );
    }

    public function testOnlyMongoTransportOptionsAreOverriddenForBehat(): void
    {
        $testDsn = 'mongodb://test-server';
        $expectedDriverOptions = ['context' => ['ssl' => ['verify_peer' => true]]];
        $container = $this->mongoContainer();

        (new EnvironmentKernel('prod', false, dirname(__DIR__, 4), $testDsn))
            ->process($container);

        $arguments = $container
            ->getDefinition('doctrine_mongodb.odm.default_connection')
            ->getArguments();

        self::assertSame($testDsn, $arguments[0]);
        self::assertSame($expectedDriverOptions, $arguments[2]);
        self::assertSame(false, $arguments[1]['tls']);
        self::assertFalse(isset($arguments[1]['tlsCAFile']));
        self::assertFalse($arguments[1]['retryWrites']);
    }

    private function mongoContainer(): ContainerBuilder
    {
        $options = ['tls' => true, 'tlsCAFile' => '/ca.pem', 'retryWrites' => false];
        $driverOptions = ['context' => ['ssl' => ['verify_peer' => true]]];
        $container = new ContainerBuilder();
        $container->setDefinition(
            'doctrine_mongodb.odm.default_connection',
            new Definition(
                'MongoDB\\Client',
                ['mongodb://production-server', $options, $driverOptions]
            )
        );

        return $container;
    }
}
