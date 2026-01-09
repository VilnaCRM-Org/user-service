<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Command;

use App\Shared\Application\Fixture\SchemathesisFixtures;
use App\Shared\Infrastructure\Seeder\PasswordResetTokenSeeder;
use App\Shared\Infrastructure\Seeder\SchemathesisConfirmationTokenSeeder;
use App\Shared\Infrastructure\Seeder\SchemathesisOAuthSeeder;
use App\Shared\Infrastructure\Seeder\SchemathesisUserSeeder;
use App\User\Domain\Repository\PasswordResetTokenRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed-schemathesis-data',
    description: self::COMMAND_DESCRIPTION
)]
final class SeedSchemathesisDataCommand extends Command
{
    private const COMMAND_DESCRIPTION = 'Seed schemathesis reference data.';

    public function __construct(
        private readonly SchemathesisUserSeeder $userSeeder,
        private readonly PasswordResetTokenSeeder $passwordResetTokenSeeder,
        private readonly SchemathesisOAuthSeeder $oauthSeeder,
        private readonly SchemathesisConfirmationTokenSeeder $confirmationTokenSeeder,
        private readonly PasswordResetTokenRepositoryInterface $passwordResetTokenRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $io = new SymfonyStyle($input, $output);

        $this->resetPersistentState();

        $seededUsers = $this->userSeeder->seedUsers();
        $primaryUser = $seededUsers['primary'];
        $passwordResetConfirmUser = $seededUsers['password_reset_confirm'];

        $this->confirmationTokenSeeder->seedToken($primaryUser);
        $this->passwordResetTokenSeeder->seedTokens(
            $passwordResetConfirmUser,
            [
                SchemathesisFixtures::PASSWORD_RESET_CONFIRM_TOKEN,
                SchemathesisFixtures::PASSWORD_RESET_CONFIRM_TOKEN_LD,
            ]
        );
        $client = $this->oauthSeeder->seedClient();
        $this->oauthSeeder->seedAuthorizationCode($client, $primaryUser);

        $io->success('Schemathesis reference data has been seeded.');

        return Command::SUCCESS;
    }

    private function resetPersistentState(): void
    {
        $this->passwordResetTokenRepository->deleteAll();
        $this->userRepository->deleteAll();
    }
}
