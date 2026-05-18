<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Command\Fixture;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\UserInterface;

final class InMemoryUserRepositoryTest extends UnitTestCase
{
    public function testFindByEmailsUsesNormalizedCaseInsensitiveMatching(): void
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
}
