<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Seeder;

use App\Shared\Application\Fixture\SchemathesisFixtures;
use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Seeder\PasswordResetTokenSeeder;
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

    public function testSeedTokensRemovesExistingTokenBeforeCreating(): void
    {
        $repository = new InMemoryPasswordResetTokenRepository();
        $user = $this->createTestUser();

        $existingToken = new PasswordResetToken(
            'existing-token',
            $user->getId(),
            new DateTimeImmutable('+1 hour'),
            new DateTimeImmutable()
        );
        $repository->save($existingToken);

        $seeder = new PasswordResetTokenSeeder($repository);
        $seeder->seedTokens($user, ['existing-token']);

        $this->assertSame(1, $repository->deleteCount());
        $this->assertCount(1, $repository->all());
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
