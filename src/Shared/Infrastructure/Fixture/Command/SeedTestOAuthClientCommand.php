<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Fixture\Command;

use App\Shared\Infrastructure\Fixture\Seeder\SchemathesisOAuthSeeder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed-test-oauth-client',
    description: self::COMMAND_DESCRIPTION
)]
final class SeedTestOAuthClientCommand extends Command
{
    private const COMMAND_DESCRIPTION = 'Seed deterministic OAuth client for test runs.';

    public function __construct(
        private readonly SchemathesisOAuthSeeder $oauthSeeder
    ) {
        parent::__construct();
    }

    /**
     * @return int
     */
    #[\Override]
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $io = new SymfonyStyle($input, $output);

        $this->oauthSeeder->seedClient();
        $io->success('Test OAuth client has been seeded.');

        return Command::SUCCESS;
    }
}
