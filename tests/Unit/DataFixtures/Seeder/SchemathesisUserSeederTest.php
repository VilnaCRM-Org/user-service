<?php

declare(strict_types=1);

namespace App\Tests\Unit\DataFixtures\Seeder;

use App\Shared\Application\Fixture\SchemathesisFixtures;
use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Fixture\Seeder\SchemathesisUserSeeder;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\Shared\Application\Command\Fixture\HashingPasswordHasherFactory;
use App\Tests\Unit\Shared\Application\Command\Fixture\InMemoryUserRepository;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Factory\UserFactory;

final class SchemathesisUserSeederTest extends UnitTestCase
{
    public function testSeedUsersCreatesAllUsers(): void
    {
        $seeder = $this->createSeeder(new InMemoryUserRepository());

        $users = $seeder->seedUsers();

        $this->assertCount(5, $users);
        $this->assertArrayHasKey('primary', $users);
        $this->assertArrayHasKey('update', $users);
        $this->assertArrayHasKey('delete', $users);
        $this->assertArrayHasKey('password_reset_request', $users);
        $this->assertArrayHasKey('password_reset_confirm', $users);
    }

    public function testSeedUsersUpdatesExistingUser(): void
    {
        $uuidTransformer = new UuidTransformer(new UuidFactory());
        $userFactory = new UserFactory();
        $existingUser = $userFactory->create(
            'old-email@example.com',
            'OldInitials',
            'OldPassword1!',
            $uuidTransformer->transformFromString(SchemathesisFixtures::UPDATE_USER_ID)
        );
        $repository = new InMemoryUserRepository($existingUser);
        $seeder = $this->createSeeder($repository, $uuidTransformer, $userFactory);

        $users = $seeder->seedUsers();

        $updateUser = $users['update'];
        $this->assertSame($existingUser, $updateUser);
        $this->assertSame(SchemathesisFixtures::UPDATE_USER_EMAIL, $updateUser->getEmail());
        $this->assertSame(SchemathesisFixtures::UPDATE_USER_INITIALS, $updateUser->getInitials());
    }

    public function testSeedUsersUpdatesEmailOnExistingUser(): void
    {
        $uuidTransformer = new UuidTransformer(new UuidFactory());
        $userFactory = new UserFactory();
        $existingUser = $userFactory->create(
            'different@example.com',
            SchemathesisFixtures::USER_INITIALS,
            'OldPassword1!',
            $uuidTransformer->transformFromString(SchemathesisFixtures::USER_ID)
        );
        $repository = new InMemoryUserRepository($existingUser);
        $seeder = $this->createSeeder($repository, $uuidTransformer, $userFactory);

        $users = $seeder->seedUsers();

        $this->assertSame(SchemathesisFixtures::USER_EMAIL, $users['primary']->getEmail());
        $this->assertNotSame('different@example.com', $users['primary']->getEmail());
    }

    public function testSeedUsersUpdatesInitialsOnExistingUser(): void
    {
        $uuidTransformer = new UuidTransformer(new UuidFactory());
        $userFactory = new UserFactory();
        $existingUser = $userFactory->create(
            SchemathesisFixtures::USER_EMAIL,
            'DifferentInitials',
            'OldPassword1!',
            $uuidTransformer->transformFromString(SchemathesisFixtures::USER_ID)
        );
        $repository = new InMemoryUserRepository($existingUser);
        $seeder = $this->createSeeder($repository, $uuidTransformer, $userFactory);

        $users = $seeder->seedUsers();

        $this->assertSame(SchemathesisFixtures::USER_INITIALS, $users['primary']->getInitials());
        $this->assertNotSame('DifferentInitials', $users['primary']->getInitials());
    }

    public function testSeedUsersCreatesNewUserWhenNotFound(): void
    {
        $repository = new InMemoryUserRepository();
        $seeder = $this->createSeeder($repository);

        $users = $seeder->seedUsers();

        $this->assertSame(SchemathesisFixtures::USER_ID, $users['primary']->getId());
        $this->assertSame(SchemathesisFixtures::USER_EMAIL, $users['primary']->getEmail());
    }

    private function createSeeder(
        InMemoryUserRepository $repository,
        ?UuidTransformer $uuidTransformer = null,
        ?UserFactory $userFactory = null
    ): SchemathesisUserSeeder {
        return new SchemathesisUserSeeder(
            $repository,
            $userFactory ?? new UserFactory(),
            new HashingPasswordHasherFactory(),
            $uuidTransformer ?? new UuidTransformer(new UuidFactory())
        );
    }
}
