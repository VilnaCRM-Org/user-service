<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Repository;

final class CachedUserRepositoryWriteOperationsTest extends CachedUserRepositoryTestCase
{
    public function testSaveDelegatesToInnerRepository(): void
    {
        $user = $this->createUserMock($this->faker->uuid(), $this->faker->email());

        $this->innerRepository
            ->expects($this->once())
            ->method('save')
            ->with($user);

        $this->repository->save($user);
    }

    public function testDeleteDelegatesToInnerRepository(): void
    {
        $user = $this->createUserMock($this->faker->uuid(), $this->faker->email());

        $this->innerRepository
            ->expects($this->once())
            ->method('delete')
            ->with($user);

        $this->repository->delete($user);
    }

    public function testSaveBatchDelegatesToInnerRepository(): void
    {
        $users = [
            $this->createUserMock($this->faker->uuid(), $this->faker->email()),
            $this->createUserMock($this->faker->uuid(), $this->faker->email()),
        ];

        $this->innerRepository
            ->expects($this->once())
            ->method('saveBatch')
            ->with($users);

        $this->repository->saveBatch($users);
    }

    public function testDeleteBatchDelegatesToInnerRepository(): void
    {
        $users = [
            $this->createUserMock($this->faker->uuid(), $this->faker->email()),
            $this->createUserMock($this->faker->uuid(), $this->faker->email()),
        ];

        $this->innerRepository
            ->expects($this->once())
            ->method('deleteBatch')
            ->with($users);

        $this->repository->deleteBatch($users);
    }

    public function testDeleteAllInvalidatesCache(): void
    {
        $this->innerRepository
            ->expects($this->once())
            ->method('deleteAll');

        $this->cache
            ->expects($this->once())
            ->method('invalidateTags')
            ->with(['user', 'user.collection']);

        $this->repository->deleteAll();
    }

    public function testDeleteAllLogsWarningWhenInvalidationFails(): void
    {
        $this->innerRepository
            ->expects($this->once())
            ->method('deleteAll');

        $this->cache
            ->expects($this->once())
            ->method('invalidateTags')
            ->with(['user', 'user.collection'])
            ->willThrowException(new \RuntimeException('Cache error'));

        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with(
                'Failed to invalidate cache after deleteAll',
                $this->callback(
                    static fn (array $context): bool => isset($context['error'])
                        && $context['operation'] === 'cache.invalidation.error'
                )
            );

        $this->repository->deleteAll();
    }

    public function testMagicCallDelegatesToInnerRepository(): void
    {
        $id = $this->faker->uuid();
        $user = $this->createUserMock($id);

        $this->innerRepository
            ->expects($this->once())
            ->method('findById')
            ->with($id)
            ->willReturn($user);

        $result = $this->repository->__call('findById', [$id]);

        self::assertSame($user, $result);
    }
}
