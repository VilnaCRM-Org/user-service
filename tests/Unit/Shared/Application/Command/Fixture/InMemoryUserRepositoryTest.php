<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Command\Fixture;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\UserInterface;
use function mb_strtolower;
use function mb_strtoupper;

final class InMemoryUserRepositoryTest extends UnitTestCase
{
    public function testFindByEmailsUsesExactCandidateMatching(): void
    {
        $email = $this->faker->email();
        $storedUser = $this->createConfiguredMock(UserInterface::class, [
            'getId' => $this->faker->uuid(),
            'getEmail' => $email,
        ]);
        $repository = new InMemoryUserRepository($storedUser);

        $this->assertSame(
            [$storedUser],
            iterator_to_array($repository->findByEmails([mb_strtoupper($email)]), false)
        );
        $this->assertSame(
            [$storedUser],
            iterator_to_array($repository->findByEmails(['  ' . $email . '  ']), false)
        );
    }

    public function testFindByEmailsDoesNotMatchLegacyMixedCaseEmail(): void
    {
        $email = $this->faker->email();
        $legacyEmail = mb_strtoupper($email, 'UTF-8');
        $storedUser = $this->createConfiguredMock(UserInterface::class, [
            'getId' => $this->faker->uuid(),
            'getEmail' => $legacyEmail,
        ]);
        $repository = new InMemoryUserRepository($storedUser);

        $this->assertSame(
            [],
            iterator_to_array($repository->findByEmails([mb_strtolower($email, 'UTF-8')]), false)
        );
    }

    public function testFindByEmailCaseInsensitiveMatchesLegacyMixedCaseEmail(): void
    {
        $email = $this->faker->email();
        $storedUser = $this->createConfiguredMock(UserInterface::class, [
            'getId' => $this->faker->uuid(),
            'getEmail' => mb_strtoupper($email, 'UTF-8'),
        ]);
        $repository = new InMemoryUserRepository($storedUser);

        $this->assertSame(
            [$storedUser],
            iterator_to_array($repository->findByEmailCaseInsensitive($email), false)
        );
    }
}
