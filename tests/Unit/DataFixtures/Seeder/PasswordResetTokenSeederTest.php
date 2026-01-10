<?php

declare(strict_types=1);

namespace App\Tests\Unit\DataFixtures\Seeder;

use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Fixture\SchemathesisFixtures;
use App\Shared\Infrastructure\Fixture\Seeder\PasswordResetTokenSeeder;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\Shared\Application\Command\Fixture\InMemoryPasswordResetTokenRepository;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\PasswordResetToken;
use App\User\Domain\Factory\UserFactory;
use DateTimeImmutable;

final class PasswordResetTokenSeederTest extends UnitTestCase
{
    public function testSeedTokensCreatesTokens(): void
    {
        $repository = new InMemoryPasswordResetTokenRepository();
        $seeder = new PasswordResetTokenSeeder($repository);
        $user = $this->createTestUser();

        $seeder->seedTokens($user, ['token1', 'token2']);

        $tokens = $repository->all();
        $this->assertCount(2, $tokens);
        $this->assertArrayHasKey('token1', $tokens);
        $this->assertArrayHasKey('token2', $tokens);
    }

    public function testSeedTokensUpdatesExistingTokenInsteadOfDeleting(): void
    {
        $repository = new InMemoryPasswordResetTokenRepository();
        $user = $this->createTestUser();

        $expiredTime = new DateTimeImmutable('-1 hour');
        $existingToken = new PasswordResetToken(
            'existing-token',
            $user->getId(),
            $expiredTime,
            new DateTimeImmutable('-2 hours')
        );
        $existingToken->markAsUsed();
        $repository->save($existingToken);

        $seeder = new PasswordResetTokenSeeder($repository);
        $seeder->seedTokens($user, ['existing-token']);

        $this->assertSame(0, $repository->deleteCount());
        $this->assertCount(1, $repository->all());

        $updatedToken = $repository->findByToken('existing-token');
        $this->assertNotNull($updatedToken);
        $this->assertFalse($updatedToken->isExpired());
        $this->assertFalse($updatedToken->isUsed());
    }

    public function testSeedTokensDoesNotDeleteWhenNoExistingToken(): void
    {
        $repository = new InMemoryPasswordResetTokenRepository();
        $seeder = new PasswordResetTokenSeeder($repository);
        $user = $this->createTestUser();

        $seeder->seedTokens($user, ['new-token']);

        $this->assertSame(0, $repository->deleteCount());
        $this->assertCount(1, $repository->all());
    }

    public function testSeedTokensSetsCorrectUserId(): void
    {
        $repository = new InMemoryPasswordResetTokenRepository();
        $seeder = new PasswordResetTokenSeeder($repository);
        $user = $this->createTestUser();

        $seeder->seedTokens($user, ['test-token']);

        $token = $repository->findByToken('test-token');
        $this->assertNotNull($token);
        $this->assertSame($user->getId(), $token->getUserID());
    }

    public function testSeedTokensExtendsExpirationForExistingToken(): void
    {
        $repository = new InMemoryPasswordResetTokenRepository();
        $user = $this->createTestUser();

        $expiredTime = new DateTimeImmutable('-2 hours');
        $existingToken = new PasswordResetToken(
            'test-token',
            $user->getId(),
            $expiredTime,
            new DateTimeImmutable('-3 hours')
        );
        $repository->save($existingToken);

        $this->assertTrue($existingToken->isExpired());

        $seeder = new PasswordResetTokenSeeder($repository);
        $seeder->seedTokens($user, ['test-token']);

        $refreshedToken = $repository->findByToken('test-token');
        $this->assertNotNull($refreshedToken);
        $this->assertFalse($refreshedToken->isExpired());
    }

    public function testSeedTokensResetsUsageForExistingToken(): void
    {
        $repository = new InMemoryPasswordResetTokenRepository();
        $user = $this->createTestUser();

        $existingToken = new PasswordResetToken(
            'used-token',
            $user->getId(),
            new DateTimeImmutable('+1 hour'),
            new DateTimeImmutable()
        );
        $existingToken->markAsUsed();
        $repository->save($existingToken);

        $this->assertTrue($existingToken->isUsed());

        $seeder = new PasswordResetTokenSeeder($repository);
        $seeder->seedTokens($user, ['used-token']);

        $refreshedToken = $repository->findByToken('used-token');
        $this->assertNotNull($refreshedToken);
        $this->assertFalse($refreshedToken->isUsed());
    }

    public function testSeedTokensReturnsRefreshedTokenNotNewOne(): void
    {
        $repository = new InMemoryPasswordResetTokenRepository();
        $user = $this->createTestUser();

        $originalCreatedAt = new DateTimeImmutable('-1 day');
        $existingToken = new PasswordResetToken(
            'existing-token',
            $user->getId(),
            new DateTimeImmutable('-1 hour'),
            $originalCreatedAt
        );
        $repository->save($existingToken);

        $seeder = new PasswordResetTokenSeeder($repository);
        $seeder->seedTokens($user, ['existing-token']);

        $savedTokens = $repository->getSavedBatchTokens();
        $this->assertCount(1, $savedTokens);

        $savedToken = $savedTokens[0];
        $this->assertSame($existingToken, $savedToken);
        $this->assertSame(
            $originalCreatedAt->getTimestamp(),
            $savedToken->getCreatedAt()->getTimestamp()
        );
    }

    private function createTestUser(): \App\User\Domain\Entity\UserInterface
    {
        $uuidTransformer = new UuidTransformer(new UuidFactory());
        $userFactory = new UserFactory();
        $userId = $uuidTransformer->transformFromString(
            SchemathesisFixtures::PASSWORD_RESET_CONFIRM_USER_ID
        );

        return $userFactory->create(
            'test@example.com',
            'Test User',
            'Password1!',
            $userId
        );
    }
}
