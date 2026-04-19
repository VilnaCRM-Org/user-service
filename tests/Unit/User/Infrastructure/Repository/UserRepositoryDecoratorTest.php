<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Repository;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Collection\UserCollection;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Repository\UserRepositoryDecorator;

final class UserRepositoryDecoratorTest extends UnitTestCase
{
    public function testDeleteAllDelegatesToInnerRepository(): void
    {
        $innerRepository = $this->createMock(UserRepositoryInterface::class);

        $innerRepository
            ->expects($this->once())
            ->method('deleteAll');

        $decorator = $this->createDecorator($innerRepository);
        $decorator->deleteAll();
    }

    private function createDecorator(
        UserRepositoryInterface $innerRepository
    ): UserRepositoryDecorator {
        return new class($innerRepository) extends UserRepositoryDecorator {
            #[\Override]
            public function findByEmail(string $email): ?UserInterface
            {
                return $this->inner->findByEmail($email);
            }

            /**
             * @param array<int, string> $emails
             */
            #[\Override]
            public function findByEmails(array $emails): UserCollection
            {
                return $this->inner->findByEmails($emails);
            }

            #[\Override]
            public function findById(string $id): ?UserInterface
            {
                return $this->inner->findById($id);
            }
        };
    }
}
