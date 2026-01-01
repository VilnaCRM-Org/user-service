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
        $deps = $this->createDependenciesWithoutExistingClient();
        $command = $this->createSeedCommandFromDeps($deps);

        $tester = new CommandTester($command);
        $status = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $status);
        $this->assertNull($deps['clientManager']->removedClient());
        $this->assertNotNull($deps['clientManager']->savedClient());
    }

    /**
     * @return array{
     *     userRepository: InMemoryUserRepository,
     *     userFactory: UserFactory,
     *     hasherFactory: HashingPasswordHasherFactory,
     *     uuidTransformer: UuidTransformer,
     *     tokenRepository: InMemoryConfirmationTokenRepository,
     *     passwordResetTokenRepository: InMemoryPasswordResetTokenRepository,
     *     clientManager: RecordingClientManager,
     *     authorizationCodeManager: RecordingAuthorizationCodeManager,
     *     connection: Connection
     * }
     */
    private function createDependenciesWithoutExistingClient(): array
    {
        $uuidTransformer = new UuidTransformer(new UuidFactory());
        $userFactory = new UserFactory();
        $userRepository = new InMemoryUserRepository();
        $hasherFactory = new HashingPasswordHasherFactory();
        $tokenRepository = new InMemoryConfirmationTokenRepository();
        $passwordResetTokenRepository = new InMemoryPasswordResetTokenRepository();
        $clientManager = new RecordingClientManager(null);
        $authorizationCodeManager = new RecordingAuthorizationCodeManager();

        $connection = $this->createConnectionForMissingClient();

        return [
            'userRepository' => $userRepository,
            'userFactory' => $userFactory,
            'hasherFactory' => $hasherFactory,
            'uuidTransformer' => $uuidTransformer,
            'tokenRepository' => $tokenRepository,
            'passwordResetTokenRepository' => $passwordResetTokenRepository,
            'clientManager' => $clientManager,
            'authorizationCodeManager' => $authorizationCodeManager,
            'connection' => $connection,
        ];
    }

    private function createConnectionForMissingClient(): Connection
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->exactly(2))
            ->method('executeStatement')
            ->willReturnCallback(
                $this->expectSequential(
                    [
                        ['DELETE FROM password_reset_tokens'],
                        ['DELETE FROM `user`'],
                    ],
                    1
                )
            );

        $connection->expects($this->exactly(3))
            ->method('delete')
            ->willReturn(1);

        return $connection;
    }

    /**
     * @param array{
     *     userRepository: InMemoryUserRepository,
     *     userFactory: UserFactory,
     *     hasherFactory: HashingPasswordHasherFactory,
     *     uuidTransformer: UuidTransformer,
     *     tokenRepository: InMemoryConfirmationTokenRepository,
     *     passwordResetTokenRepository: InMemoryPasswordResetTokenRepository,
     *     clientManager: RecordingClientManager,
     *     authorizationCodeManager: RecordingAuthorizationCodeManager,
     *     connection: Connection
     * } $deps
     */
    private function createSeedCommandFromDeps(array $deps): SeedSchemathesisDataCommand
    {
        return new SeedSchemathesisDataCommand(
            $this->createUserSeederFromDeps($deps),
            $this->createPasswordResetTokenSeederFromDeps($deps),
            $this->createOAuthSeederFromDeps($deps),
            $deps['tokenRepository'],
            $deps['connection']
        );
    }

    /**
     * @param array{
     *     userRepository: InMemoryUserRepository,
     *     userFactory: UserFactory,
     *     hasherFactory: HashingPasswordHasherFactory,
     *     uuidTransformer: UuidTransformer,
     *     tokenRepository: InMemoryConfirmationTokenRepository,
     *     passwordResetTokenRepository: InMemoryPasswordResetTokenRepository,
     *     clientManager: RecordingClientManager,
     *     authorizationCodeManager: RecordingAuthorizationCodeManager,
     *     connection: Connection
     * } $deps
     */
    private function createUserSeederFromDeps(array $deps): UserSeeder
    {
        return $this->createUserSeeder(
            $deps['userRepository'],
            $deps['userFactory'],
            $deps['hasherFactory'],
            $deps['uuidTransformer']
        );
    }

    /**
     * @param array{
     *     userRepository: InMemoryUserRepository,
     *     userFactory: UserFactory,
     *     hasherFactory: HashingPasswordHasherFactory,
     *     uuidTransformer: UuidTransformer,
     *     tokenRepository: InMemoryConfirmationTokenRepository,
     *     passwordResetTokenRepository: InMemoryPasswordResetTokenRepository,
     *     clientManager: RecordingClientManager,
     *     authorizationCodeManager: RecordingAuthorizationCodeManager,
     *     connection: Connection
     * } $deps
     */
    private function createPasswordResetTokenSeederFromDeps(array $deps): PasswordResetTokenSeeder
    {
        return new PasswordResetTokenSeeder(
            $deps['connection'],
            $deps['passwordResetTokenRepository']
        );
    }

    /**
     * @param array{
     *     userRepository: InMemoryUserRepository,
     *     userFactory: UserFactory,
     *     hasherFactory: HashingPasswordHasherFactory,
     *     uuidTransformer: UuidTransformer,
     *     tokenRepository: InMemoryConfirmationTokenRepository,
     *     passwordResetTokenRepository: InMemoryPasswordResetTokenRepository,
     *     clientManager: RecordingClientManager,
     *     authorizationCodeManager: RecordingAuthorizationCodeManager,
     *     connection: Connection
     * } $deps
     */
    private function createOAuthSeederFromDeps(array $deps): OAuthSeeder
    {
        return $this->createOAuthSeeder(
            $deps['clientManager'],
            $deps['connection'],
            $deps['authorizationCodeManager']
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
        $uuidTransformer = new UuidTransformer(new UuidFactory());
        $userFactory = new UserFactory();
        $existingUpdateUser = $this->createExistingUpdateUser($userFactory, $uuidTransformer);

        $repos = $this->createRepositoriesForSeeding($existingUpdateUser);
        $oauth = $this->createOAuthDependencies();
        $connection = $this->createConnectionMockForSeeding();

        $command = $this->createSeedCommandFromComponents(
            $repos,
            $userFactory,
            $uuidTransformer,
            $connection,
            $oauth
        );

        return array_merge(
            ['command' => $command, 'existingUpdateUser' => $existingUpdateUser],
            $repos,
            $oauth
        );
    }

    private function createConnectionMockForSeeding(): Connection
    {
        $connection = $this->createMock(Connection::class);

        $this->setupExecuteStatementExpectations($connection);
        $this->setupDeleteExpectations($connection);

        return $connection;
    }

    private function setupExecuteStatementExpectations(Connection $connection): void
    {
        $connection->expects($this->exactly(2))
            ->method('executeStatement')
            ->willReturnCallback(
                $this->expectSequential(
                    [
                        ['DELETE FROM password_reset_tokens'],
                        ['DELETE FROM `user`'],
                    ],
                    1
                )
            );
    }

    private function setupDeleteExpectations(Connection $connection): void
    {
        $confirmTokenLd = SchemathesisFixtures::PASSWORD_RESET_CONFIRM_TOKEN_LD;

        $connection->expects($this->exactly(3))
            ->method('delete')
            ->willReturnCallback(
                $this->expectSequential(
                    [
                        [
                            'password_reset_tokens',
                            ['token_value' => SchemathesisFixtures::PASSWORD_RESET_CONFIRM_TOKEN],
                        ],
                        [
                            'password_reset_tokens',
                            ['token_value' => $confirmTokenLd],
                        ],
                        [
                            'oauth2_authorization_code',
                            ['identifier' => SchemathesisFixtures::AUTHORIZATION_CODE],
                        ],
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
        $userSeeder = $this->createUserSeeder(
            $userRepository,
            $userFactory,
            $hasherFactory,
            $uuidTransformer
        );
        $passwordResetTokenSeeder = new PasswordResetTokenSeeder(
            $connection,
            $passwordResetTokenRepository
        );
        $oauthSeeder = $this->createOAuthSeeder(
            $clientManager,
            $connection,
            $authorizationCodeManager
        );

        return new SeedSchemathesisDataCommand(
            $userSeeder,
            $passwordResetTokenSeeder,
            $oauthSeeder,
            $tokenRepository,
            $connection
        );
    }

    private function createUserSeeder(
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

    private function createOAuthSeeder(
        RecordingClientManager $clientManager,
        Connection $connection,
        RecordingAuthorizationCodeManager $authorizationCodeManager
    ): SchemathesisOAuthSeeder {
        return new SchemathesisOAuthSeeder(
            $clientManager,
            $connection,
            $authorizationCodeManager
        );
    }

    private function executeCommandAndAssertSuccess(
        SeedSchemathesisDataCommand $command
    ): CommandTester {
        $tester = new CommandTester($command);
        $status = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $status);
        $this->assertStringContainsString(
            'Schemathesis reference data has been seeded.',
            $tester->getDisplay()
        );

        return $tester;
    }

    private function assertUsersWereSeeded(
        InMemoryUserRepository $userRepository,
        UserInterface $existingUpdateUser
    ): void {
        $users = $userRepository->all();

        $this->assertUserKeysExist($users);
        $this->assertUserStates($users, $existingUpdateUser);
    }

    /**
     * @param array<string, UserInterface> $users
     */
    private function assertUserKeysExist(array $users): void
    {
        $this->assertArrayHasKey(SchemathesisFixtures::USER_ID, $users);
        $this->assertArrayHasKey(SchemathesisFixtures::UPDATE_USER_ID, $users);
        $this->assertArrayHasKey(SchemathesisFixtures::DELETE_USER_ID, $users);
        $this->assertArrayHasKey(SchemathesisFixtures::PASSWORD_RESET_REQUEST_USER_ID, $users);
        $this->assertArrayHasKey(SchemathesisFixtures::PASSWORD_RESET_CONFIRM_USER_ID, $users);
    }

    /**
     * @param array<string, UserInterface> $users
     */
    private function assertUserStates(array $users, UserInterface $existingUpdateUser): void
    {
        $this->assertMainUserState($users);
        $this->assertUpdateUserState($users, $existingUpdateUser);
        $this->assertPasswordResetUsersConfirmed($users);
    }

    /**
     * @param array<string, UserInterface> $users
     */
    private function assertMainUserState(array $users): void
    {
        $this->assertFalse($users[SchemathesisFixtures::USER_ID]->isConfirmed());
        $this->assertSame(
            'hashed-'.SchemathesisFixtures::USER_PASSWORD,
            $users[SchemathesisFixtures::USER_ID]->getPassword()
        );
    }

    /**
     * @param array<string, UserInterface> $users
     */
    private function assertUpdateUserState(array $users, UserInterface $existingUpdateUser): void
    {
        $this->assertSame(
            SchemathesisFixtures::UPDATE_USER_EMAIL,
            $users[SchemathesisFixtures::UPDATE_USER_ID]->getEmail()
        );
        $this->assertSame(
            SchemathesisFixtures::UPDATE_USER_INITIALS,
            $users[SchemathesisFixtures::UPDATE_USER_ID]->getInitials()
        );
        $this->assertFalse($users[SchemathesisFixtures::UPDATE_USER_ID]->isConfirmed());
        $this->assertSame($existingUpdateUser, $users[SchemathesisFixtures::UPDATE_USER_ID]);
    }

    /**
     * @param array<string, UserInterface> $users
     */
    private function assertPasswordResetUsersConfirmed(array $users): void
    {
        $this->assertTrue($users[SchemathesisFixtures::DELETE_USER_ID]->isConfirmed());
        $this->assertTrue(
            $users[SchemathesisFixtures::PASSWORD_RESET_REQUEST_USER_ID]->isConfirmed()
        );
        $this->assertTrue(
            $users[SchemathesisFixtures::PASSWORD_RESET_CONFIRM_USER_ID]->isConfirmed()
        );
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

    private function createExistingUpdateUser(
        UserFactory $userFactory,
        UuidTransformer $uuidTransformer
    ): UserInterface {
        return $userFactory->create(
            'old-update@example.com',
            'OldInitials',
            'OldPassword1!',
            $uuidTransformer->transformFromString(SchemathesisFixtures::UPDATE_USER_ID)
        );
    }

    /**
     * @return array{
     *     userRepository: InMemoryUserRepository,
     *     tokenRepository: InMemoryConfirmationTokenRepository,
     *     passwordResetTokenRepository: InMemoryPasswordResetTokenRepository
     * }
     */
    private function createRepositoriesForSeeding(UserInterface $existingUpdateUser): array
    {
        return [
            'userRepository' => new InMemoryUserRepository($existingUpdateUser),
            'tokenRepository' => new InMemoryConfirmationTokenRepository(),
            'passwordResetTokenRepository' => new InMemoryPasswordResetTokenRepository(),
        ];
    }

    /**
     * @return array{
     *     clientManager: RecordingClientManager,
     *     authorizationCodeManager: RecordingAuthorizationCodeManager
     * }
     */
    private function createOAuthDependencies(): array
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

    /**
     * @param array{
     *     userRepository: InMemoryUserRepository,
     *     tokenRepository: InMemoryConfirmationTokenRepository,
     *     passwordResetTokenRepository: InMemoryPasswordResetTokenRepository
     * } $repos
     * @param array{
     *     clientManager: RecordingClientManager,
     *     authorizationCodeManager: RecordingAuthorizationCodeManager
     * } $oauth
     */
    private function createSeedCommandFromComponents(
        array $repos,
        UserFactory $userFactory,
        UuidTransformer $uuidTransformer,
        Connection $connection,
        array $oauth
    ): SeedSchemathesisDataCommand {
        return $this->createSeedCommand(
            $repos['userRepository'],
            $userFactory,
            new HashingPasswordHasherFactory(),
            $uuidTransformer,
            $connection,
            $repos['passwordResetTokenRepository'],
            $oauth['clientManager'],
            $oauth['authorizationCodeManager'],
            $repos['tokenRepository']
        );
    }
}
