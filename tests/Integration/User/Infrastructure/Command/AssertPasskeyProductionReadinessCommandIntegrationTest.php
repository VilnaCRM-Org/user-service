<?php

declare(strict_types=1);

namespace App\Tests\Integration\User\Infrastructure\Command;

use App\Tests\Integration\IntegrationTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;

final class AssertPasskeyProductionReadinessCommandIntegrationTest extends IntegrationTestCase
{
    public function testCommandPassesAfterRealMongoSchemaUpdate(): void
    {
        $application = $this->createApplication();
        $schemaTester = new CommandTester($application->find('doctrine:mongodb:schema:update'));
        $schemaStatus = $schemaTester->execute(
            ['--skip-search-indexes' => true],
            ['interactive' => false]
        );

        self::assertSame(Command::SUCCESS, $schemaStatus);

        $tester = new CommandTester($application->find(
            'app:passkey:assert-production-readiness'
        ));

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertStringContainsString(
            'Passkey production readiness verified.',
            $tester->getDisplay()
        );
    }

    private function createApplication(): Application
    {
        $kernel = $this->container->get('kernel');
        self::assertInstanceOf(KernelInterface::class, $kernel);

        $application = new Application($kernel);
        $application->setAutoExit(false);

        return $application;
    }
}
