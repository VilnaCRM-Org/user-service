<?php

declare(strict_types=1);

namespace App\Tests\Unit\DataFixtures\Command;

use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Fixture\Command\SeedSchemathesisDataCommand;
use App\Shared\Infrastructure\Fixture\SchemathesisFixtures;
use App\Shared\Infrastructure\Fixture\Seeder\PasswordResetTokenSeeder;
use App\Shared\Infrastructure\Fixture\Seeder\SchemathesisConfirmationTokenSeeder;
use App\Shared\Infrastructure\Fixture\Seeder\SchemathesisOAuthSeeder;
use App\Shared\Infrastructure\Fixture\Seeder\SchemathesisUserSeeder;
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
use Doctrine\ODM\MongoDB\DocumentManager;
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

        $this->assertUsersWereSeeded($deps['userRepository']);
        $this->assertConfirmationToken($deps['tokenRepository']);
        $this->assertPasswordResetTokens($deps['passwordResetTokenRepository']);
        $this->assertOAuthClient($deps['clientManager']);
        $this->assertAuthorizationCode($deps['authorizationCodeManager']);
    }

    public function testExecuteCallsResetPersistentState(): void
    {
        $deps = $this->createSeedCommandDependencies();
        $this->executeCommandAndAssertSuccess($deps['command']);

        $this->assertSame(1, $deps['userRepository']->deleteAllCount());
        $this->assertSame(1, $deps['passwordResetTokenRepository']->deleteAllCount());
    }

    public function testExecuteDoesNotRemoveMissingClient(): void
    {
        $deps = $this->createMissingClientTestDependencies();
        $command = $this->buildCommandFromDeps($deps);

        $tester = new CommandTester($command);
        $status = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $status);
        $this->assertNull($deps['clientManager']->removedClient());
        $this->assertNotNull($deps['clientManager']->savedClient());
    }

    public function testExecuteRemovesExistingPasswordResetToken(): void
    {
        $deps = $this->createExistingPasswordResetTokenDependencies();
        $command = $this->buildCommandFromDeps($deps);

        $tester = new CommandTester($command);
        $status = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $status);
        $storedTokens = $deps['passwordResetTokenRepository']->all();
        $this->assertCount(2, $storedTokens);
        $this->assertArrayHasKey(
            SchemathesisFixtures::PASSWORD_RESET_CONFIRM_TOKEN,
            $storedTokens
        );
        $this->assertSame(1, $deps['passwordResetTokenRepository']->deleteCount());
    }

    /**
     * @return array{
     *     clientManager: RecordingClientManager,
     *     documentManager: DocumentManager,
     *     tokenRepository: InMemoryConfirmationTokenRepository,
     *     passwordResetTokenRepository: InMemoryPasswordResetTokenRepository,
     *     userSeeder: SchemathesisUserSeeder,
     *     userRepository: InMemoryUserRepository,
     *     authorizationCodeManager: RecordingAuthorizationCodeManager
     * }
     */
    private function createMissingClientTestDependencies(): array
    {
        $uuidTransformer = new UuidTransformer(new UuidFactory());
        $clientManager = new RecordingClientManager(null);
        $documentManager = $this->createDocumentManagerMockForOAuth();
        $userRepository = new InMemoryUserRepository();

        return [
            'clientManager' => $clientManager,
            'documentManager' => $documentManager,
            'tokenRepository' => new InMemoryConfirmationTokenRepository(),
            'passwordResetTokenRepository' => new InMemoryPasswordResetTokenRepository(),
            'userSeeder' => $this->buildUserSeeder(
                $userRepository,
                new UserFactory(),
                new HashingPasswordHasherFactory(),
                $uuidTransformer
            ),
            'userRepository' => $userRepository,
            'authorizationCodeManager' => new RecordingAuthorizationCodeManager(),
        ];
    }

    private function createDocumentManagerMockForOAuth(): DocumentManager
    {
        $documentManager = $this->createMock(DocumentManager::class);
        // The seeder calls find(), and if nothing exists, doesn't call remove()
        $documentManager->expects($this->never())->method('remove');
        $documentManager->expects($this->never())->method('flush');

        return $documentManager;
    }

    /**
     * @param array{
     *     clientManager: RecordingClientManager,
     *     documentManager: DocumentManager,
     *     tokenRepository: InMemoryConfirmationTokenRepository,
     *     passwordResetTokenRepository: InMemoryPasswordResetTokenRepository,
     *     userSeeder: SchemathesisUserSeeder,
     *     userRepository: InMemoryUserRepository,
     *     authorizationCodeManager: RecordingAuthorizationCodeManager
     * } $deps
     */
    private function buildCommandFromDeps(array $deps): SeedSchemathesisDataCommand
    {
        $oauthSeeder = new SchemathesisOAuthSeeder(
            $deps['clientManager'],
            $deps['documentManager'],
            $deps['authorizationCodeManager']
        );
        $passwordSeeder = new PasswordResetTokenSeeder(
            $deps['passwordResetTokenRepository']
        );
        $confirmationTokenSeeder = new SchemathesisConfirmationTokenSeeder(
            $deps['tokenRepository']
        );

        return new SeedSchemathesisDataCommand(
            $deps['userSeeder'],
            $passwordSeeder,
            $oauthSeeder,
            $confirmationTokenSeeder,
            $deps['passwordResetTokenRepository'],
            $deps['userRepository']
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
        $documentManager = $this->createDocumentManagerMockForSeeding();
        $command = $this->createSeedCommand(
            $repos['userRepository'],
            $repos['userFactory'],
            $repos['hasherFactory'],
            $repos['uuidTransformer'],
            $documentManager,
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

    private function createDocumentManagerMockForSeeding(): DocumentManager
    {
        $documentManager = $this->createMock(DocumentManager::class);
        // The seeder calls find() and if nothing exists, doesn't call remove()
        $documentManager->expects($this->never())->method('remove');
        $documentManager->expects($this->never())->method('flush');

        return $documentManager;
    }

    private function createSeedCommand(
        InMemoryUserRepository $userRepository,
        UserFactory $userFactory,
        HashingPasswordHasherFactory $hasherFactory,
        UuidTransformer $uuidTransformer,
        DocumentManager $documentManager,
        InMemoryPasswordResetTokenRepository $passwordResetTokenRepository,
        RecordingClientManager $clientManager,
        RecordingAuthorizationCodeManager $authorizationCodeManager,
        InMemoryConfirmationTokenRepository $tokenRepository
    ): SeedSchemathesisDataCommand {
        return new SeedSchemathesisDataCommand(
            $this->buildUserSeeder($userRepository, $userFactory, $hasherFactory, $uuidTransformer),
            new PasswordResetTokenSeeder($passwordResetTokenRepository),
            new SchemathesisOAuthSeeder($clientManager, $documentManager, $authorizationCodeManager),
            new SchemathesisConfirmationTokenSeeder($tokenRepository),
            $passwordResetTokenRepository,
            $userRepository
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
        InMemoryUserRepository $userRepository
    ): void {
        $users = $userRepository->all();

        $this->assertArrayHasKey(SchemathesisFixtures::USER_ID, $users);
        $this->assertArrayHasKey(SchemathesisFixtures::UPDATE_USER_ID, $users);
        $this->assertArrayHasKey(SchemathesisFixtures::DELETE_USER_ID, $users);
        $this->assertArrayHasKey(SchemathesisFixtures::PASSWORD_RESET_REQUEST_USER_ID, $users);
        $this->assertArrayHasKey(SchemathesisFixtures::PASSWORD_RESET_CONFIRM_USER_ID, $users);

        $this->assertMainAndUpdateUserStates($users);
        $this->assertConfirmedUsersState($users);
    }

    /**
     * @param array<string, UserInterface> $users
     */
    private function assertMainAndUpdateUserStates(array $users): void
    {
        $mainUser = $users[SchemathesisFixtures::USER_ID];
        $this->assertFalse($mainUser->isConfirmed());
        $this->assertSame('hashed-'.SchemathesisFixtures::USER_PASSWORD, $mainUser->getPassword());

        $updateUser = $users[SchemathesisFixtures::UPDATE_USER_ID];
        $this->assertSame(SchemathesisFixtures::UPDATE_USER_EMAIL, $updateUser->getEmail());
        $this->assertSame(SchemathesisFixtures::UPDATE_USER_INITIALS, $updateUser->getInitials());
        $this->assertFalse($updateUser->isConfirmed());
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

    /**
     * @return (DocumentManager|InMemoryConfirmationTokenRepository|InMemoryPasswordResetTokenRepository|InMemoryUserRepository|RecordingAuthorizationCodeManager|RecordingClientManager|SchemathesisUserSeeder)[]
     *
     * @psalm-return array{clientManager: RecordingClientManager, documentManager: DocumentManager, tokenRepository: InMemoryConfirmationTokenRepository, passwordResetTokenRepository: InMemoryPasswordResetTokenRepository, userSeeder: SchemathesisUserSeeder, userRepository: InMemoryUserRepository, authorizationCodeManager: RecordingAuthorizationCodeManager}
     */
    private function createExistingPasswordResetTokenDependencies(): array
    {
        $uuidTransformer = new UuidTransformer(new UuidFactory());
        $userFactory = new UserFactory();
        $hasherFactory = new HashingPasswordHasherFactory();
        $repo = new InMemoryPasswordResetTokenRepository();
        $userRepository = new InMemoryUserRepository();
        $this->seedExistingToken($repo, $userFactory, $uuidTransformer);

        return [
            'clientManager' => new RecordingClientManager(null),
            'documentManager' => $this->createDocumentManagerMockForOAuth(),
            'tokenRepository' => new InMemoryConfirmationTokenRepository(),
            'passwordResetTokenRepository' => $repo,
            'userSeeder' => $this->buildUserSeeder(
                $userRepository,
                $userFactory,
                $hasherFactory,
                $uuidTransformer
            ),
            'userRepository' => $userRepository,
            'authorizationCodeManager' => new RecordingAuthorizationCodeManager(),
        ];
    }

    private function seedExistingToken(
        InMemoryPasswordResetTokenRepository $repo,
        UserFactory $userFactory,
        UuidTransformer $uuidTransformer
    ): void {
        $userId = $uuidTransformer->transformFromString(
            SchemathesisFixtures::PASSWORD_RESET_CONFIRM_USER_ID
        );
        $user = $userFactory->create('test@example.com', 'Test User', 'Password1!', $userId);
        $repo->save(new \App\User\Domain\Entity\PasswordResetToken(
            SchemathesisFixtures::PASSWORD_RESET_CONFIRM_TOKEN,
            $user->getId(),
            new DateTimeImmutable('+1 hour'),
            new DateTimeImmutable()
        ));
    }
}
