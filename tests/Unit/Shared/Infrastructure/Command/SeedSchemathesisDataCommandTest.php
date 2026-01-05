<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Command;

use App\Shared\Application\Fixture\SchemathesisFixtures;
use App\Shared\Infrastructure\Command\Seeder\PasswordResetTokenSeeder;
use App\Shared\Infrastructure\Command\Seeder\SchemathesisOAuthSeeder;
use App\Shared\Infrastructure\Command\Seeder\SchemathesisUserSeeder;
use App\Shared\Infrastructure\Command\SeedSchemathesisDataCommand;
use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\Shared\Application\Command\Fixture\HashingPasswordHasherFactory;
use App\Tests\Unit\Shared\Application\Command\Fixture\InMemoryConfirmationTokenRepository;
use App\Tests\Unit\Shared\Application\Command\Fixture\InMemoryPasswordResetTokenRepository;
use App\Tests\Unit\Shared\Application\Command\Fixture\InMemoryUserRepository;
use App\Tests\Unit\Shared\Application\Command\Fixture\RecordingAuthorizationCodeManager;
use App\Tests\Unit\Shared\Application\Command\Fixture\RecordingClientManager;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\ConfirmationToken;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Factory\UserFactory;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\ValueObject\Scope;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class SeedSchemathesisDataCommandTest extends UnitTestCase
{
    public function testExecuteSeedsReferenceData(): void
    {
        $deps = $this->createSeedCommandDependencies();
        $this->executeCommandAndAssertSuccess($deps['command']);

        $this->assertUsersWereSeeded($deps['userRepository'], $deps['existingUpdateUser']);
        $this->assertTokensWereSeeded(
            $deps['tokenRepository'],
            $deps['passwordResetTokenRepository']
        );
        $this->assertOAuthDataWasSeeded($deps['clientManager'], $deps['authorizationCodeManager']);
    }

    public function testExecuteDoesNotRemoveMissingClient(): void
    {
        $deps = $this->createMissingClientTestDependencies();
        $command = $this->buildCommandForMissingClientTest($deps);

        $tester = new CommandTester($command);
        $status = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $status);
        $this->assertNull($deps['clientManager']->removedClient());
        $this->assertNotNull($deps['clientManager']->savedClient());
    }

    /**
     * @return array{
     *     clientManager: RecordingClientManager,
     *     connection: Connection,
     *     tokenRepository: InMemoryConfirmationTokenRepository,
     *     passwordResetTokenRepository: InMemoryPasswordResetTokenRepository,
     *     userSeeder: SchemathesisUserSeeder,
     *     authorizationCodeManager: RecordingAuthorizationCodeManager
     * }
     */
    private function createMissingClientTestDependencies(): array
    {
        $uuidTransformer = new UuidTransformer(new UuidFactory());
        $clientManager = new RecordingClientManager(null);
        $connection = $this->createMissingClientConnectionMock();

        return [
            'clientManager' => $clientManager,
            'connection' => $connection,
            'tokenRepository' => new InMemoryConfirmationTokenRepository(),
            'passwordResetTokenRepository' => new InMemoryPasswordResetTokenRepository(),
            'userSeeder' => $this->buildUserSeeder(
                new InMemoryUserRepository(),
                new UserFactory(),
                new HashingPasswordHasherFactory(),
                $uuidTransformer
            ),
            'authorizationCodeManager' => new RecordingAuthorizationCodeManager(),
        ];
    }

    private function createMissingClientConnectionMock(): Connection
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->exactly(2))
            ->method('executeStatement')
            ->willReturnCallback(
                $this->expectSequential(
                    [['DELETE FROM password_reset_tokens'], ['DELETE FROM `user`']],
                    1
                )
            );
        $connection->expects($this->exactly(3))->method('delete')->willReturn(1);

        return $connection;
    }

    /**
     * @param array{
     *     clientManager: RecordingClientManager,
     *     connection: Connection,
     *     tokenRepository: InMemoryConfirmationTokenRepository,
     *     passwordResetTokenRepository: InMemoryPasswordResetTokenRepository,
     *     userSeeder: SchemathesisUserSeeder,
     *     authorizationCodeManager: RecordingAuthorizationCodeManager
     * } $deps
     */
    private function buildCommandForMissingClientTest(array $deps): SeedSchemathesisDataCommand
    {
        $oauthSeeder = new SchemathesisOAuthSeeder(
            $deps['clientManager'],
            $deps['connection'],
            $deps['authorizationCodeManager']
        );
        $passwordSeeder = new PasswordResetTokenSeeder(
            $deps['connection'],
            $deps['passwordResetTokenRepository']
        );

        return new SeedSchemathesisDataCommand(
            $deps['userSeeder'],
            $passwordSeeder,
            $oauthSeeder,
            $deps['tokenRepository'],
            $deps['connection']
        );
    }

    /**
     * @return array{
     *     command: SeedSchemathesisDataCommand,
     *     userRepository: InMemoryUserRepository,
     *     tokenRepository: InMemoryConfirmationTokenRepository,
     *     passwordResetTokenRepository: InMemoryPasswordResetTokenRepository,
     *     clientManager: RecordingClientManager,
     *     authorizationCodeManager: RecordingAuthorizationCodeManager,
     *     existingUpdateUser: UserInterface
     * }
     */
    private function createSeedCommandDependencies(): array
    {
        $repos = $this->createRepositories();
        $managers = $this->createManagers();
        $connection = $this->createConnectionMockForSeeding();
        $command = $this->createSeedCommand(
            $repos['userRepository'],
            $repos['userFactory'],
            $repos['hasherFactory'],
            $repos['uuidTransformer'],
            $connection,
            $repos['passwordResetTokenRepository'],
            $managers['clientManager'],
            $managers['authorizationCodeManager'],
            $repos['tokenRepository']
        );

        return $this->buildDependenciesResult($repos, $managers, $command);
    }

    /**
     * @param array{
     *     userRepository: InMemoryUserRepository,
     *     tokenRepository: InMemoryConfirmationTokenRepository,
     *     passwordResetTokenRepository: InMemoryPasswordResetTokenRepository,
     *     existingUpdateUser: UserInterface
     * } $repos
     * @param array{
     *     clientManager: RecordingClientManager,
     *     authorizationCodeManager: RecordingAuthorizationCodeManager
     * } $managers
     *
     * @return array{
     *     command: SeedSchemathesisDataCommand,
     *     userRepository: InMemoryUserRepository,
     *     tokenRepository: InMemoryConfirmationTokenRepository,
     *     passwordResetTokenRepository: InMemoryPasswordResetTokenRepository,
     *     clientManager: RecordingClientManager,
     *     authorizationCodeManager: RecordingAuthorizationCodeManager,
     *     existingUpdateUser: UserInterface
     * }
     */
    private function buildDependenciesResult(
        array $repos,
        array $managers,
        SeedSchemathesisDataCommand $command
    ): array {
        return [
            'command' => $command,
            'userRepository' => $repos['userRepository'],
            'tokenRepository' => $repos['tokenRepository'],
            'passwordResetTokenRepository' => $repos['passwordResetTokenRepository'],
            'clientManager' => $managers['clientManager'],
            'authorizationCodeManager' => $managers['authorizationCodeManager'],
            'existingUpdateUser' => $repos['existingUpdateUser'],
        ];
    }

    /**
     * @return array{
     *     userRepository: InMemoryUserRepository,
     *     userFactory: UserFactory,
     *     hasherFactory: HashingPasswordHasherFactory,
     *     uuidTransformer: UuidTransformer,
     *     tokenRepository: InMemoryConfirmationTokenRepository,
     *     passwordResetTokenRepository: InMemoryPasswordResetTokenRepository,
     *     existingUpdateUser: UserInterface
     * }
     */
    private function createRepositories(): array
    {
        $uuidTransformer = new UuidTransformer(new UuidFactory());
        $userFactory = new UserFactory();
        $existingUpdateUser = $userFactory->create(
            'old-update@example.com',
            'OldInitials',
            'OldPassword1!',
            $uuidTransformer->transformFromString(SchemathesisFixtures::UPDATE_USER_ID)
        );

        return [
            'userRepository' => new InMemoryUserRepository($existingUpdateUser),
            'userFactory' => $userFactory,
            'hasherFactory' => new HashingPasswordHasherFactory(),
            'uuidTransformer' => $uuidTransformer,
            'tokenRepository' => new InMemoryConfirmationTokenRepository(),
            'passwordResetTokenRepository' => new InMemoryPasswordResetTokenRepository(),
            'existingUpdateUser' => $existingUpdateUser,
        ];
    }

    /**
     * @return array{
     *     clientManager: RecordingClientManager,
     *     authorizationCodeManager: RecordingAuthorizationCodeManager
     * }
     */
    private function createManagers(): array
    {
        $existingClient = new Client(
            'Old Client',
            SchemathesisFixtures::OAUTH_CLIENT_ID,
            'old-secret'
        );

        return [
            'clientManager' => new RecordingClientManager($existingClient),
            'authorizationCodeManager' => new RecordingAuthorizationCodeManager(),
        ];
    }

    private function createConnectionMockForSeeding(): Connection
    {
        $connection = $this->createMock(Connection::class);
        $this->configureExecuteStatementExpectations($connection);
        $this->configureDeleteExpectations($connection);

        return $connection;
    }

    private function configureExecuteStatementExpectations(Connection $connection): void
    {
        $connection->expects($this->exactly(2))
            ->method('executeStatement')
            ->willReturnCallback(
                $this->expectSequential(
                    [['DELETE FROM password_reset_tokens'], ['DELETE FROM `user`']],
                    1
                )
            );
    }

    private function configureDeleteExpectations(Connection $connection): void
    {
        $tokenLd = SchemathesisFixtures::PASSWORD_RESET_CONFIRM_TOKEN_LD;
        $token = SchemathesisFixtures::PASSWORD_RESET_CONFIRM_TOKEN;
        $authCode = SchemathesisFixtures::AUTHORIZATION_CODE;

        $connection->expects($this->exactly(3))
            ->method('delete')
            ->willReturnCallback(
                $this->expectSequential(
                    [
                        ['password_reset_tokens', ['token_value' => $token]],
                        ['password_reset_tokens', ['token_value' => $tokenLd]],
                        ['oauth2_authorization_code', ['identifier' => $authCode]],
                    ],
                    1
                )
            );
    }

    private function createSeedCommand(
        InMemoryUserRepository $userRepository,
        UserFactory $userFactory,
        HashingPasswordHasherFactory $hasherFactory,
        UuidTransformer $uuidTransformer,
        Connection $connection,
        InMemoryPasswordResetTokenRepository $passwordResetTokenRepository,
        RecordingClientManager $clientManager,
        RecordingAuthorizationCodeManager $authorizationCodeManager,
        InMemoryConfirmationTokenRepository $tokenRepository
    ): SeedSchemathesisDataCommand {
        return new SeedSchemathesisDataCommand(
            $this->buildUserSeeder($userRepository, $userFactory, $hasherFactory, $uuidTransformer),
            new PasswordResetTokenSeeder($connection, $passwordResetTokenRepository),
            new SchemathesisOAuthSeeder($clientManager, $connection, $authorizationCodeManager),
            $tokenRepository,
            $connection
        );
    }

    private function buildUserSeeder(
        InMemoryUserRepository $userRepository,
        UserFactory $userFactory,
        HashingPasswordHasherFactory $hasherFactory,
        UuidTransformer $uuidTransformer
    ): SchemathesisUserSeeder {
        return new SchemathesisUserSeeder(
            $userRepository,
            $userFactory,
            $hasherFactory,
            $uuidTransformer
        );
    }

    private function executeCommandAndAssertSuccess(
        SeedSchemathesisDataCommand $command
    ): void {
        $tester = new CommandTester($command);
        $status = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $status);
        $this->assertStringContainsString(
            'Schemathesis reference data has been seeded.',
            $tester->getDisplay()
        );
    }

    private function assertUsersWereSeeded(
        InMemoryUserRepository $userRepository,
        UserInterface $existingUpdateUser
    ): void {
        $users = $userRepository->all();

        $this->assertArrayHasKey(SchemathesisFixtures::USER_ID, $users);
        $this->assertArrayHasKey(SchemathesisFixtures::UPDATE_USER_ID, $users);
        $this->assertArrayHasKey(SchemathesisFixtures::DELETE_USER_ID, $users);
        $this->assertArrayHasKey(SchemathesisFixtures::PASSWORD_RESET_REQUEST_USER_ID, $users);
        $this->assertArrayHasKey(SchemathesisFixtures::PASSWORD_RESET_CONFIRM_USER_ID, $users);

        $this->assertMainAndUpdateUserStates($users, $existingUpdateUser);
        $this->assertConfirmedUsersState($users);
    }

    /**
     * @param array<string, UserInterface> $users
     */
    private function assertMainAndUpdateUserStates(
        array $users,
        UserInterface $existingUpdateUser
    ): void {
        $mainUser = $users[SchemathesisFixtures::USER_ID];
        $this->assertFalse($mainUser->isConfirmed());
        $this->assertSame('hashed-'.SchemathesisFixtures::USER_PASSWORD, $mainUser->getPassword());

        $updateUser = $users[SchemathesisFixtures::UPDATE_USER_ID];
        $this->assertSame(SchemathesisFixtures::UPDATE_USER_EMAIL, $updateUser->getEmail());
        $this->assertSame(SchemathesisFixtures::UPDATE_USER_INITIALS, $updateUser->getInitials());
        $this->assertFalse($updateUser->isConfirmed());
        $this->assertSame($existingUpdateUser, $updateUser);
    }

    /**
     * @param array<string, UserInterface> $users
     */
    private function assertConfirmedUsersState(array $users): void
    {
        $deleteUser = $users[SchemathesisFixtures::DELETE_USER_ID];
        $resetRequestUser = $users[SchemathesisFixtures::PASSWORD_RESET_REQUEST_USER_ID];
        $resetConfirmUser = $users[SchemathesisFixtures::PASSWORD_RESET_CONFIRM_USER_ID];

        $this->assertTrue($deleteUser->isConfirmed());
        $this->assertTrue($resetRequestUser->isConfirmed());
        $this->assertTrue($resetConfirmUser->isConfirmed());
    }

    private function assertTokensWereSeeded(
        InMemoryConfirmationTokenRepository $tokenRepository,
        InMemoryPasswordResetTokenRepository $passwordResetTokenRepository
    ): void {
        $this->assertConfirmationToken($tokenRepository);
        $this->assertPasswordResetTokens($passwordResetTokenRepository);
    }

    private function assertConfirmationToken(
        InMemoryConfirmationTokenRepository $tokenRepository
    ): void {
        $token = $tokenRepository->getToken();
        $this->assertInstanceOf(ConfirmationToken::class, $token);
        $this->assertSame(SchemathesisFixtures::CONFIRMATION_TOKEN, $token->getTokenValue());
        $deltaInSeconds = (new DateTimeImmutable())->getTimestamp()
            - $token->getAllowedToSendAfter()->getTimestamp();
        $this->assertGreaterThanOrEqual(55, $deltaInSeconds);
        $this->assertLessThanOrEqual(65, $deltaInSeconds);
        $this->assertSame(0, $token->getTimesSent());
    }

    private function assertPasswordResetTokens(
        InMemoryPasswordResetTokenRepository $passwordResetTokenRepository
    ): void {
        $confirmTokenLd = SchemathesisFixtures::PASSWORD_RESET_CONFIRM_TOKEN_LD;
        $storedTokens = $passwordResetTokenRepository->all();
        $this->assertCount(2, $storedTokens);
        $this->assertArrayHasKey(
            SchemathesisFixtures::PASSWORD_RESET_CONFIRM_TOKEN,
            $storedTokens
        );
        $this->assertArrayHasKey(
            $confirmTokenLd,
            $storedTokens
        );
        $this->assertSame(
            SchemathesisFixtures::PASSWORD_RESET_CONFIRM_USER_ID,
            $storedTokens[SchemathesisFixtures::PASSWORD_RESET_CONFIRM_TOKEN]->getUserID()
        );
        $this->assertSame(
            SchemathesisFixtures::PASSWORD_RESET_CONFIRM_USER_ID,
            $storedTokens[$confirmTokenLd]->getUserID()
        );
    }

    private function assertOAuthDataWasSeeded(
        RecordingClientManager $clientManager,
        RecordingAuthorizationCodeManager $authorizationCodeManager
    ): void {
        $this->assertOAuthClient($clientManager);
        $this->assertAuthorizationCode($authorizationCodeManager);
    }

    private function assertOAuthClient(RecordingClientManager $clientManager): void
    {
        $this->assertNotNull($clientManager->removedClient());
        $this->assertSame(
            SchemathesisFixtures::OAUTH_CLIENT_ID,
            $clientManager->removedClient()->getIdentifier()
        );
        $this->assertNotNull($clientManager->savedClient());
        $savedClient = $clientManager->savedClient();
        $this->assertSame(SchemathesisFixtures::OAUTH_CLIENT_SECRET, $savedClient->getSecret());
        $this->assertSame(
            ['email', 'profile'],
            array_map(
                static fn (Scope $scope): string => (string) $scope,
                $savedClient->getScopes()
            )
        );
        $this->assertTrue($savedClient->isActive());
        $this->assertFalse($savedClient->isPlainTextPkceAllowed());
    }

    private function assertAuthorizationCode(
        RecordingAuthorizationCodeManager $authorizationCodeManager
    ): void {
        $this->assertNotNull($authorizationCodeManager->savedCode());
        $savedCode = $authorizationCodeManager->savedCode();
        $this->assertSame(SchemathesisFixtures::AUTHORIZATION_CODE, $savedCode->getIdentifier());
        $this->assertSame(SchemathesisFixtures::USER_ID, $savedCode->getUserIdentifier());
        $this->assertSame(
            ['email'],
            array_map(
                static fn (Scope $scope): string => (string) $scope,
                $savedCode->getScopes()
            )
        );
    }
}
