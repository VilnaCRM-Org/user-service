<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Repository;

use App\User\Domain\Collection\UserCollection;

final class CachedUserRepositoryWriteOperationsTest extends CachedUserRepositoryTestCase
{
    public function testSaveDelegatesToInnerRepository(): void
    {
        $user = $this->createUserMock($this->faker->uuid(), $this->faker->email());
        $hash = $this->faker->sha256();

        $this->innerRepository
            ->expects($this->once())
            ->method('save')
            ->with($user);

        $this->expectHashEmail($user->getEmail(), $hash);
        $this->expectInvalidateTags([
            'user.collection',
            'user.' . $user->getId(),
            'user.email.' . $hash,
        ]);

        $this->repository->save($user);
    }

    public function testSaveInvalidatesPreviousEmailTagWhenEmailChanges(): void
    {
        $oldEmail = $this->faker->unique()->email();
        $newEmail = $this->faker->unique()->email();
        $oldHash = $this->faker->sha256();
        $newHash = $this->faker->sha256();
        $user = $this->createUserMock($this->faker->uuid(), $newEmail);

        $this->innerRepository
            ->expects($this->once())
            ->method('save')
            ->with($user);

        $this->unitOfWork
            ->expects($this->once())
            ->method('getOriginalDocumentData')
            ->with($user)
            ->willReturn(['email' => $oldEmail]);

        $this->cacheKeyBuilder
            ->method('hashEmail')
            ->willReturnCallback(static function (string $email) use ($oldEmail, $oldHash, $newEmail, $newHash): string {
                return match ($email) {
                    $oldEmail => $oldHash,
                    $newEmail => $newHash,
                    default => throw new \LogicException(sprintf('Unexpected email "%s".', $email)),
                };
            });
        $this->expectInvalidateTags([
            'user.collection',
            'user.' . $user->getId(),
            'user.email.' . $newHash,
            'user.email.' . $oldHash,
        ]);

        $this->repository->save($user);
    }

    public function testDeleteDelegatesToInnerRepository(): void
    {
        $user = $this->createUserMock($this->faker->uuid(), $this->faker->email());
        $hash = $this->faker->sha256();

        $this->innerRepository
            ->expects($this->once())
            ->method('delete')
            ->with($user);

        $this->expectHashEmail($user->getEmail(), $hash);
        $this->expectInvalidateTags([
            'user.collection',
            'user.' . $user->getId(),
            'user.email.' . $hash,
        ]);

        $this->repository->delete($user);
    }

    public function testSaveBatchDelegatesToInnerRepository(): void
    {
        $firstUser = $this->createUserMock($this->faker->uuid(), $this->faker->email());
        $secondUser = $this->createUserMock($this->faker->uuid(), $this->faker->email());
        $users = new UserCollection([
            $firstUser,
            $secondUser,
        ]);
        $firstHash = $this->faker->sha256();
        $secondHash = $this->faker->sha256();

        $this->innerRepository
            ->expects($this->once())
            ->method('saveBatch')
            ->with($users);

        $this->expectHashEmails([
            $firstUser->getEmail() => $firstHash,
            $secondUser->getEmail() => $secondHash,
        ]);
        $this->expectInvalidateTags([
            'user.collection',
            'user.' . $firstUser->getId(),
            'user.email.' . $firstHash,
            'user.' . $secondUser->getId(),
            'user.email.' . $secondHash,
        ]);

        $this->repository->saveBatch($users);
    }

    public function testFindByEmailsDelegatesToInnerRepository(): void
    {
        $firstEmail = $this->faker->unique()->email();
        $secondEmail = $this->faker->unique()->email();
        $user = $this->createUserMock($this->faker->uuid(), $firstEmail);

        $this->innerRepository
            ->expects($this->once())
            ->method('findByEmails')
            ->with([$firstEmail, $secondEmail])
            ->willReturn(new UserCollection([$user]));

        $result = $this->repository->findByEmails([$firstEmail, $secondEmail]);

        self::assertSame([$user], iterator_to_array($result));
    }

    public function testDeleteBatchDelegatesToInnerRepository(): void
    {
        $firstUser = $this->createUserMock($this->faker->uuid(), $this->faker->email());
        $secondUser = $this->createUserMock($this->faker->uuid(), $this->faker->email());
        $users = new UserCollection([
            $firstUser,
            $secondUser,
        ]);
        $firstHash = $this->faker->sha256();
        $secondHash = $this->faker->sha256();

        $this->innerRepository
            ->expects($this->once())
            ->method('deleteBatch')
            ->with($users);

        $this->expectHashEmails([
            $firstUser->getEmail() => $firstHash,
            $secondUser->getEmail() => $secondHash,
        ]);
        $this->expectInvalidateTags([
            'user.collection',
            'user.' . $firstUser->getId(),
            'user.email.' . $firstHash,
            'user.' . $secondUser->getId(),
            'user.email.' . $secondHash,
        ]);

        $this->repository->deleteBatch($users);
    }

    public function testSaveLogsWarningWhenInvalidationFails(): void
    {
        $user = $this->createUserMock($this->faker->uuid(), $this->faker->email());
        $hash = $this->faker->sha256();

        $this->innerRepository
            ->expects($this->once())
            ->method('save')
            ->with($user);

        $this->expectHashEmail($user->getEmail(), $hash);
        $this->cache->expectInvalidateTags(static function (array $tags) use ($user, $hash): never {
            self::assertSame([
                'user.collection',
                'user.' . $user->getId(),
                'user.email.' . $hash,
            ], $tags);

            throw new \RuntimeException('Cache error');
        });

        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with(
                'Failed to invalidate cache after user write',
                $this->callback(
                    static fn (array $context): bool => isset($context['error'])
                        && $context['operation'] === 'cache.invalidation.error'
                        && $context['write_operation'] === 'save'
                )
            );

        $this->repository->save($user);
    }

    public function testDeleteAllInvalidatesCache(): void
    {
        $this->innerRepository
            ->expects($this->once())
            ->method('deleteAll');

        $this->cache->expectInvalidateTags(static function (array $tags): bool {
            self::assertSame(['user', 'user.collection'], $tags);

            return true;
        });

        $this->repository->deleteAll();
    }

    public function testDeleteAllLogsWarningWhenInvalidationFails(): void
    {
        $this->innerRepository
            ->expects($this->once())
            ->method('deleteAll');

        $this->cache->expectInvalidateTags(static function (array $tags): never {
            self::assertSame(['user', 'user.collection'], $tags);

            throw new \RuntimeException('Cache error');
        });

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

    public function testFindByIdDelegatesToInnerRepository(): void
    {
        $id = $this->faker->uuid();
        $user = $this->createUserMock($id);

        $this->innerRepository
            ->expects($this->once())
            ->method('findById')
            ->with($id)
            ->willReturn($user);

        $result = $this->repository->findById($id);

        self::assertSame($user, $result);
    }

    private function expectHashEmail(string $email, string $hash): void
    {
        $this->cacheKeyBuilder
            ->expects($this->once())
            ->method('hashEmail')
            ->with($email)
            ->willReturn($hash);
    }

    /**
     * @param array<string, string> $hashesByEmail
     */
    private function expectHashEmails(array $hashesByEmail): void
    {
        $this->cacheKeyBuilder
            ->expects($this->exactly(count($hashesByEmail)))
            ->method('hashEmail')
            ->willReturnCallback(static function (string $email) use ($hashesByEmail): string {
                self::assertArrayHasKey($email, $hashesByEmail);

                return $hashesByEmail[$email];
            });
    }

    /**
     * @param list<string> $expectedTags
     */
    private function expectInvalidateTags(array $expectedTags): void
    {
        $this->cache->expectInvalidateTags(static function (array $tags) use ($expectedTags): bool {
            self::assertSame($expectedTags, $tags);

            return true;
        });
    }
}
