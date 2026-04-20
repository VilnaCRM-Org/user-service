<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Repository;

use App\User\Domain\Collection\UserCollection;
use App\User\Domain\Entity\UserInterface;

final class CachedUserRepositoryWriteOperationsTest extends CachedUserRepositoryTestCase
{
    public function testSaveDelegatesToInnerRepository(): void
    {
        $user = $this->createUserMock($this->faker->uuid(), $this->faker->email());
        $hash = $this->faker->sha256();

        $this->expectSingleUserWrite('save', $user, $hash);

        $this->repository->save($user);
    }

    public function testSaveInvalidatesPreviousEmailTagWhenEmailChanges(): void
    {
        $oldEmail = $this->faker->unique()->email();
        $newEmail = $this->faker->unique()->email();
        $oldHash = $this->faker->sha256();
        $newHash = $this->faker->sha256();
        $user = $this->createUserMock($this->faker->uuid(), $newEmail);

        $this->expectSaveWithPreviousEmail(
            $user,
            $oldEmail,
            $oldHash,
            $newEmail,
            $newHash
        );

        $this->repository->save($user);
    }

    public function testDeleteDelegatesToInnerRepository(): void
    {
        $user = $this->createUserMock($this->faker->uuid(), $this->faker->email());
        $hash = $this->faker->sha256();

        $this->expectSingleUserWrite('delete', $user, $hash);

        $this->repository->delete($user);
    }

    public function testSaveBatchDelegatesToInnerRepository(): void
    {
        [$users, $hashesByEmail, $expectedTags] = $this->createBatchWriteFixtures();

        $this->expectBatchWrite('saveBatch', $users, $hashesByEmail, $expectedTags);

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
        [$users, $hashesByEmail, $expectedTags] = $this->createBatchWriteFixtures();

        $this->expectBatchWrite('deleteBatch', $users, $hashesByEmail, $expectedTags);

        $this->repository->deleteBatch($users);
    }

    public function testSaveLogsWarningWhenInvalidationFails(): void
    {
        $user = $this->createUserMock($this->faker->uuid(), $this->faker->email());
        $hash = $this->faker->sha256();

        $this->expectSaveDelegation($user);
        $this->expectHashEmail($user->getEmail(), $hash);
        $this->expectInvalidateTagsFailure($this->singleUserTags($user, $hash));
        $this->expectWriteInvalidationWarning('save');

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
        $this->expectInvalidateTagsFailure(['user', 'user.collection']);
        $this->expectDeleteAllInvalidationWarning();

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
            ->willReturnCallback(
                static function (string $email) use ($hashesByEmail): string {
                    self::assertArrayHasKey($email, $hashesByEmail);

                    return $hashesByEmail[$email];
                }
            );
    }

    /**
     * @param list<string> $expectedTags
     */
    private function expectInvalidateTags(array $expectedTags): void
    {
        $this->cache->expectInvalidateTags(
            static function (array $tags) use ($expectedTags): bool {
                self::assertSame($expectedTags, $tags);

                return true;
            }
        );
    }

    private function expectSingleUserWrite(
        string $method,
        UserInterface $user,
        string $hash
    ): void {
        $this->innerRepository
            ->expects($this->once())
            ->method($method)
            ->with($user);

        $this->expectHashEmail($user->getEmail(), $hash);
        $this->expectInvalidateTags($this->singleUserTags($user, $hash));
    }

    /**
     * @return list<string>
     */
    private function singleUserTags(UserInterface $user, string $hash): array
    {
        return [
            'user',
            'user.email',
            'user.collection',
            'user.' . $user->getId(),
            'user.email.' . $hash,
        ];
    }

    private function expectSaveWithPreviousEmail(
        UserInterface $user,
        string $oldEmail,
        string $oldHash,
        string $newEmail,
        string $newHash
    ): void {
        $this->expectSaveDelegation($user);
        $this->expectOriginalEmail($user, $oldEmail);
        $this->expectHashEmailsForSave($oldEmail, $oldHash, $newEmail, $newHash);
        $this->expectInvalidateTags([
            'user',
            'user.email',
            'user.collection',
            'user.' . $user->getId(),
            'user.email.' . $newHash,
            'user.email.' . $oldHash,
        ]);
    }

    private function expectSaveDelegation(UserInterface $user): void
    {
        $this->innerRepository
            ->expects($this->once())
            ->method('save')
            ->with($user);
    }

    private function expectOriginalEmail(UserInterface $user, string $oldEmail): void
    {
        $this->unitOfWork
            ->expects($this->once())
            ->method('getOriginalDocumentData')
            ->with($user)
            ->willReturn(['email' => $oldEmail]);
    }

    private function expectHashEmailsForSave(
        string $oldEmail,
        string $oldHash,
        string $newEmail,
        string $newHash
    ): void {
        $this->cacheKeyBuilder
            ->method('hashEmail')
            ->willReturnCallback(
                static function (string $email) use (
                    $oldEmail,
                    $oldHash,
                    $newEmail,
                    $newHash
                ): string {
                    return match ($email) {
                        $oldEmail => $oldHash,
                        $newEmail => $newHash,
                        default => throw new \LogicException(
                            sprintf('Unexpected email "%s".', $email)
                        ),
                    };
                }
            );
    }

    /**
     * @return array{0: UserCollection, 1: array<string, string>, 2: list<string>}
     */
    private function createBatchWriteFixtures(): array
    {
        [$firstUser, $firstHash] = $this->createBatchWriteFixture();
        [$secondUser, $secondHash] = $this->createBatchWriteFixture();

        return [
            new UserCollection([$firstUser, $secondUser]),
            [
                $firstUser->getEmail() => $firstHash,
                $secondUser->getEmail() => $secondHash,
            ],
            [
                'user',
                'user.email',
                'user.collection',
                'user.' . $firstUser->getId(),
                'user.email.' . $firstHash,
                'user.' . $secondUser->getId(),
                'user.email.' . $secondHash,
            ],
        ];
    }

    /**
     * @return array{0: UserInterface, 1: string}
     */
    private function createBatchWriteFixture(): array
    {
        return [
            $this->createUserMock($this->faker->uuid(), $this->faker->email()),
            $this->faker->sha256(),
        ];
    }

    /**
     * @param array<string, string> $hashesByEmail
     * @param list<string> $expectedTags
     */
    private function expectBatchWrite(
        string $method,
        UserCollection $users,
        array $hashesByEmail,
        array $expectedTags
    ): void {
        $this->innerRepository
            ->expects($this->once())
            ->method($method)
            ->with($users);

        $this->expectHashEmails($hashesByEmail);
        $this->expectInvalidateTags($expectedTags);
    }

    /**
     * @param list<string> $expectedTags
     */
    private function expectInvalidateTagsFailure(array $expectedTags): void
    {
        $this->cache->expectInvalidateTags(
            static function (array $tags) use ($expectedTags): never {
                self::assertSame($expectedTags, $tags);

                throw new \RuntimeException('Cache error');
            }
        );
    }

    private function expectWriteInvalidationWarning(string $writeOperation): void
    {
        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with(
                'Failed to invalidate cache after user write',
                $this->callback(
                    static fn (array $context): bool => isset($context['error'])
                        && $context['operation'] === 'cache.invalidation.error'
                        && $context['write_operation'] === $writeOperation
                )
            );
    }

    private function expectDeleteAllInvalidationWarning(): void
    {
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
    }
}
