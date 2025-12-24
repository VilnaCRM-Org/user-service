<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Command;

use App\Shared\Application\Fixture\SchemathesisFixtures;
use App\Shared\Infrastructure\Command\Seeder\PasswordResetTokenSeeder;
use App\Shared\Infrastructure\Command\Seeder\SchemathesisOAuthSeeder;
use App\Shared\Infrastructure\Command\Seeder\SchemathesisUserSeeder;
use App\User\Domain\Entity\ConfirmationToken;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Repository\TokenRepositoryInterface;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
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
        private readonly TokenRepositoryInterface $tokenRepository,
        private readonly Connection $connection,
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

        $this->seedConfirmationToken($primaryUser);
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
        $this->connection->executeStatement(
            'DELETE FROM password_reset_tokens'
        );
        $this->connection->executeStatement('DELETE FROM `user`');
    }

    private function seedConfirmationToken(UserInterface $user): void
    {
        $token = new ConfirmationToken(
            SchemathesisFixtures::CONFIRMATION_TOKEN,
            $user->getId()
        );
        $token->setAllowedToSendAfter(new DateTimeImmutable('-1 minute'));

        $this->tokenRepository->save($token);
    }
}
