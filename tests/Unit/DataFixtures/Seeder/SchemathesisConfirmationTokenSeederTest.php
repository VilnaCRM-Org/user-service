<?php

declare(strict_types=1);

namespace App\Tests\Unit\DataFixtures\Seeder;

use App\Shared\Infrastructure\Fixture\SchemathesisFixtures;
use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Fixture\Seeder\SchemathesisConfirmationTokenSeeder;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\Shared\Application\Command\Fixture\InMemoryConfirmationTokenRepository;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\ConfirmationToken;
use App\User\Domain\Factory\UserFactory;
use DateTimeImmutable;

final class SchemathesisConfirmationTokenSeederTest extends UnitTestCase
{
    public function testSeedTokenCreatesConfirmationToken(): void
    {
        $repository = new InMemoryConfirmationTokenRepository();
        $seeder = new SchemathesisConfirmationTokenSeeder($repository);

        $user = $this->createTestUser();
        $seeder->seedToken($user);

        $savedToken = $repository->getToken();
        $this->assertInstanceOf(ConfirmationToken::class, $savedToken);
        $this->assertSame(SchemathesisFixtures::CONFIRMATION_TOKEN, $savedToken->getTokenValue());
    }

    public function testSeedTokenSetsCorrectUserId(): void
    {
        $repository = new InMemoryConfirmationTokenRepository();
        $seeder = new SchemathesisConfirmationTokenSeeder($repository);

        $user = $this->createTestUser();
        $seeder->seedToken($user);

        $savedToken = $repository->getToken();
        $this->assertNotNull($savedToken);
        $this->assertSame($user->getId(), $savedToken->getUserId());
    }

    public function testSeedTokenSetsAllowedToSendAfterInPast(): void
    {
        $repository = new InMemoryConfirmationTokenRepository();
        $seeder = new SchemathesisConfirmationTokenSeeder($repository);

        $user = $this->createTestUser();
        $seeder->seedToken($user);

        $savedToken = $repository->getToken();
        $this->assertNotNull($savedToken);

        $now = new DateTimeImmutable();
        $allowedToSendAfter = $savedToken->getAllowedToSendAfter();
        $deltaInSeconds = $now->getTimestamp() - $allowedToSendAfter->getTimestamp();

        $this->assertGreaterThanOrEqual(55, $deltaInSeconds);
        $this->assertLessThanOrEqual(65, $deltaInSeconds);
    }

    private function createTestUser(): \App\User\Domain\Entity\UserInterface
    {
        $uuidTransformer = new UuidTransformer(new UuidFactory());
        $userFactory = new UserFactory();
        $userId = $uuidTransformer->transformFromString(SchemathesisFixtures::USER_ID);

        return $userFactory->create(
            'test@example.com',
            'Test User',
            'Password1!',
            $userId
        );
    }
}
