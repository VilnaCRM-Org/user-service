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
    public function testFindByEmailDelegatesToInnerRepository(): void
    {
        $email = $this->faker->email();
        $expectedUser = $this->createMock(UserInterface::class);
        $innerRepository = $this->createMock(UserRepositoryInterface::class);

        $innerRepository->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($expectedUser);

        $this->assertSame(
            $expectedUser,
            $this->createDecorator($innerRepository)->findByEmail($email)
        );
    }

    public function testFindByEmailsDelegatesToInnerRepository(): void
    {
        $emails = [$this->faker->unique()->email(), $this->faker->unique()->email()];
        $expectedUsers = new UserCollection();
        $innerRepository = $this->createMock(UserRepositoryInterface::class);

        $innerRepository->expects($this->once())
            ->method('findByEmails')
            ->with($emails)
            ->willReturn($expectedUsers);

        $this->assertSame(
            $expectedUsers,
            $this->createDecorator($innerRepository)->findByEmails($emails)
        );
    }

    public function testFindByEmailCaseInsensitiveDelegatesToInnerRepository(): void
    {
        $email = $this->faker->email();
        $expectedUsers = new UserCollection();
        $innerRepository = $this->createMock(UserRepositoryInterface::class);

        $innerRepository->expects($this->once())
            ->method('findByEmailCaseInsensitive')
            ->with($email)
            ->willReturn($expectedUsers);

        $this->assertSame(
            $expectedUsers,
            $this->createDecorator($innerRepository)
                ->findByEmailCaseInsensitive($email)
        );
    }

    public function testFindByIdDelegatesToInnerRepository(): void
    {
        $id = $this->faker->uuid();
        $expectedUser = $this->createMock(UserInterface::class);
        $innerRepository = $this->createMock(UserRepositoryInterface::class);

        $innerRepository->expects($this->once())
            ->method('findById')
            ->with($id)
            ->willReturn($expectedUser);

        $this->assertSame(
            $expectedUser,
            $this->createDecorator($innerRepository)->findById($id)
        );
    }

    public function testDeleteAllDelegatesToInnerRepository(): void
    {
        $innerRepository = $this->createMock(UserRepositoryInterface::class);

        $innerRepository
            ->expects($this->once())
            ->method('deleteAll');

        $decorator = $this->createDecorator($innerRepository);
        $decorator->deleteAll();
    }

    public function testSaveDelegatesToInnerRepository(): void
    {
        $user = new \stdClass();
        $innerRepository = $this->createMock(UserRepositoryInterface::class);

        $innerRepository->expects($this->once())
            ->method('save')
            ->with($user);

        $this->createDecorator($innerRepository)->save($user);
    }

    public function testDeleteDelegatesToInnerRepository(): void
    {
        $user = new \stdClass();
        $innerRepository = $this->createMock(UserRepositoryInterface::class);

        $innerRepository->expects($this->once())
            ->method('delete')
            ->with($user);

        $this->createDecorator($innerRepository)->delete($user);
    }

    public function testSaveBatchDelegatesToInnerRepository(): void
    {
        $users = new UserCollection();
        $innerRepository = $this->createMock(UserRepositoryInterface::class);

        $innerRepository->expects($this->once())
            ->method('saveBatch')
            ->with($users);

        $this->createDecorator($innerRepository)->saveBatch($users);
    }

    public function testDeleteBatchDelegatesToInnerRepository(): void
    {
        $users = new UserCollection();
        $innerRepository = $this->createMock(UserRepositoryInterface::class);

        $innerRepository->expects($this->once())
            ->method('deleteBatch')
            ->with($users);

        $this->createDecorator($innerRepository)->deleteBatch($users);
    }

    private function createDecorator(
        UserRepositoryInterface $innerRepository
    ): UserRepositoryDecorator {
        return new class($innerRepository) extends UserRepositoryDecorator {
        };
    }
}
