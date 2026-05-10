<?php

declare(strict_types=1);

namespace App\Tests\Unit\DataFixtures\Command;

use App\Shared\Infrastructure\Fixture\Command\SeedTestOAuthClientCommand;
use App\Shared\Infrastructure\Fixture\Seeder\SchemathesisOAuthSeeder;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class SeedTestOAuthClientCommandTest extends UnitTestCase
{
    public function testExecuteSeedsOAuthClient(): void
    {
        $oauthSeeder = $this->createMock(SchemathesisOAuthSeeder::class);
        $oauthSeeder
            ->expects($this->once())
            ->method('seedClient');

        $command = new SeedTestOAuthClientCommand($oauthSeeder);
        $tester = new CommandTester($command);

        $status = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $status);
        $this->assertStringContainsString(
            'Test OAuth client has been seeded.',
            $tester->getDisplay()
        );
    }
}
