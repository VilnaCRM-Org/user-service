<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Command;

use App\Shared\Application\Command\PasswordResetTokenSeeder;
use App\Shared\Application\Command\SchemathesisOAuthSeeder;
use App\Shared\Application\Command\SchemathesisUserSeeder;
use App\Shared\Application\Command\SeedSchemathesisDataCommand;
use App\Shared\Application\Fixture\SchemathesisFixtures;
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
        $uuidTransformer = new UuidTransformer(new UuidFactory());
        $userFactory = new UserFactory();

        $existingUpdateUser = $userFactory->create(
            'old-update@example.com',
            'OldInitials',
            'OldPassword1!',
            $uuidTransformer->transformFromString(SchemathesisFixtures::UPDATE_USER_ID)
        );

        $userRepository = new InMemoryUserRepository($existingUpdateUser);
        $hasherFactory = new HashingPasswordHasherFactory();
        $tokenRepository = new InMemoryConfirmationTokenRepository();
        $passwordResetTokenRepository = new InMemoryPasswordResetTokenRepository();
        $existingClient = new Client('Old Client', SchemathesisFixtures::OAUTH_CLIENT_ID, 'old-secret');
        $clientManager = new RecordingClientManager($existingClient);
        $authorizationCodeManager = new RecordingAuthorizationCodeManager();

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
            ->willReturnCallback(
                $this->expectSequential(
                    [
                        [
                            'password_reset_tokens',
                            ['token_value' => SchemathesisFixtures::PASSWORD_RESET_CONFIRM_TOKEN],
                        ],
                        [
                            'password_reset_tokens',
                            ['token_value' => SchemathesisFixtures::PASSWORD_RESET_CONFIRM_TOKEN_LD],
                        ],
                        [
                            'oauth2_authorization_code',
                            ['identifier' => SchemathesisFixtures::AUTHORIZATION_CODE],
                        ],
                    ],
                    1
                )
            );

        $userSeeder = new SchemathesisUserSeeder(
            $userRepository,
            $userFactory,
            $hasherFactory,
            $uuidTransformer
        );
        $passwordResetTokenSeeder = new PasswordResetTokenSeeder(
            $connection,
            $passwordResetTokenRepository
        );
        $oauthSeeder = new SchemathesisOAuthSeeder(
            $clientManager,
            $connection,
            $authorizationCodeManager
        );

        $command = new SeedSchemathesisDataCommand(
            $userSeeder,
            $passwordResetTokenSeeder,
            $oauthSeeder,
            $tokenRepository,
            $connection
        );

        $tester = new CommandTester($command);
        $status = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $status);
        $this->assertStringContainsString('Schemathesis reference data has been seeded.', $tester->getDisplay());

        $users = $userRepository->all();
        $this->assertArrayHasKey(SchemathesisFixtures::USER_ID, $users);
        $this->assertArrayHasKey(SchemathesisFixtures::UPDATE_USER_ID, $users);
        $this->assertArrayHasKey(SchemathesisFixtures::DELETE_USER_ID, $users);
        $this->assertArrayHasKey(SchemathesisFixtures::PASSWORD_RESET_REQUEST_USER_ID, $users);
        $this->assertArrayHasKey(SchemathesisFixtures::PASSWORD_RESET_CONFIRM_USER_ID, $users);

        $this->assertFalse($users[SchemathesisFixtures::USER_ID]->isConfirmed());
        $this->assertSame('hashed-'.SchemathesisFixtures::USER_PASSWORD, $users[SchemathesisFixtures::USER_ID]->getPassword());
        $this->assertSame(SchemathesisFixtures::UPDATE_USER_EMAIL, $users[SchemathesisFixtures::UPDATE_USER_ID]->getEmail());
        $this->assertSame(SchemathesisFixtures::UPDATE_USER_INITIALS, $users[SchemathesisFixtures::UPDATE_USER_ID]->getInitials());
        $this->assertFalse($users[SchemathesisFixtures::UPDATE_USER_ID]->isConfirmed());
        $this->assertSame($existingUpdateUser, $users[SchemathesisFixtures::UPDATE_USER_ID]);
        $this->assertTrue($users[SchemathesisFixtures::DELETE_USER_ID]->isConfirmed());
        $this->assertTrue($users[SchemathesisFixtures::PASSWORD_RESET_REQUEST_USER_ID]->isConfirmed());
        $this->assertTrue($users[SchemathesisFixtures::PASSWORD_RESET_CONFIRM_USER_ID]->isConfirmed());

        $token = $tokenRepository->getToken();
        $this->assertInstanceOf(ConfirmationToken::class, $token);
        $this->assertSame(SchemathesisFixtures::CONFIRMATION_TOKEN, $token->getTokenValue());
        $deltaInSeconds = (new DateTimeImmutable())->getTimestamp() - $token->getAllowedToSendAfter()->getTimestamp();
        $this->assertGreaterThanOrEqual(55, $deltaInSeconds);
        $this->assertLessThanOrEqual(65, $deltaInSeconds);
        $this->assertSame(0, $token->getTimesSent());

        $storedTokens = $passwordResetTokenRepository->all();
        $this->assertCount(2, $storedTokens);
        $this->assertArrayHasKey(SchemathesisFixtures::PASSWORD_RESET_CONFIRM_TOKEN, $storedTokens);
        $this->assertArrayHasKey(SchemathesisFixtures::PASSWORD_RESET_CONFIRM_TOKEN_LD, $storedTokens);
        $this->assertSame(
            SchemathesisFixtures::PASSWORD_RESET_CONFIRM_USER_ID,
            $storedTokens[SchemathesisFixtures::PASSWORD_RESET_CONFIRM_TOKEN]->getUserID()
        );
        $this->assertSame(
            SchemathesisFixtures::PASSWORD_RESET_CONFIRM_USER_ID,
            $storedTokens[SchemathesisFixtures::PASSWORD_RESET_CONFIRM_TOKEN_LD]->getUserID()
        );

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
            array_map(static fn (Scope $scope): string => (string) $scope, $savedClient->getScopes())
        );
        $this->assertTrue($savedClient->isActive());
        $this->assertFalse($savedClient->isPlainTextPkceAllowed());

        $this->assertNotNull($authorizationCodeManager->savedCode());
        $savedCode = $authorizationCodeManager->savedCode();
        $this->assertSame(SchemathesisFixtures::AUTHORIZATION_CODE, $savedCode->getIdentifier());
        $this->assertSame(SchemathesisFixtures::USER_ID, $savedCode->getUserIdentifier());
        $this->assertSame(['email'], array_map(static fn (Scope $scope): string => (string) $scope, $savedCode->getScopes()));
    }

    public function testExecuteDoesNotRemoveMissingClient(): void
    {
        $uuidTransformer = new UuidTransformer(new UuidFactory());
        $userFactory = new UserFactory();
        $userRepository = new InMemoryUserRepository();
        $hasherFactory = new HashingPasswordHasherFactory();
        $tokenRepository = new InMemoryConfirmationTokenRepository();
        $passwordResetTokenRepository = new InMemoryPasswordResetTokenRepository();
        $clientManager = new RecordingClientManager(null);
        $authorizationCodeManager = new RecordingAuthorizationCodeManager();

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

        $userSeeder = new SchemathesisUserSeeder(
            $userRepository,
            $userFactory,
            $hasherFactory,
            $uuidTransformer
        );
        $passwordResetTokenSeeder = new PasswordResetTokenSeeder(
            $connection,
            $passwordResetTokenRepository
        );
        $oauthSeeder = new SchemathesisOAuthSeeder(
            $clientManager,
            $connection,
            $authorizationCodeManager
        );

        $command = new SeedSchemathesisDataCommand(
            $userSeeder,
            $passwordResetTokenSeeder,
            $oauthSeeder,
            $tokenRepository,
            $connection
        );

        $tester = new CommandTester($command);
        $status = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $status);
        $this->assertNull($clientManager->removedClient());
        $this->assertNotNull($clientManager->savedClient());
    }
}
