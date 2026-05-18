<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Repository;

use App\User\Domain\Collection\UserCollection;
use App\User\Domain\Entity\UserInterface;

use function mb_strtolower;
use function mb_strtoupper;

use Symfony\Contracts\Cache\ItemInterface;

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

        $hashedEmails = [];
        $this->expectSaveWithPreviousEmail(
            $user,
            $oldEmail,
            $oldHash,
            $newEmail,
            $newHash,
            $hashedEmails
        );

        $this->repository->save($user);

        self::assertSame([$oldEmail, $newEmail], $hashedEmails);
    }

    public function testSaveSkipsPreviousEmailTagWhenEmailDoesNotChange(): void
    {
        $email = $this->faker->unique()->email();
        $hash = $this->faker->sha256();
        $user = $this->createUserMock($this->faker->uuid(), $email);

        $this->expectSaveDelegation($user);
        $this->documentManager
            ->expects($this->once())
            ->method('contains')
            ->with($user)
            ->willReturn(true);
        $this->unitOfWork
            ->expects($this->once())
            ->method('getOriginalDocumentData')
            ->with($user)
            ->willReturn(['email' => $email]);
        $this->expectHashEmail($email, $hash);
        $this->expectInvalidateTags($this->singleUserTags($user, $hash));

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

    public function testFindByEmailCaseInsensitiveReturnsCachedNormalizedEmailHit(): void
    {
        $email = $this->faker->unique()->email();
        $inputEmail = '  ' . mb_strtoupper($email, 'UTF-8') . '  ';
        $normalizedEmail = mb_strtolower($email, 'UTF-8');
        $cacheKey = 'user.email.' . $this->faker->sha256();
        $user = $this->createUserMock($this->faker->uuid(), $email);

        $this->cacheKeyBuilder
            ->expects($this->once())
            ->method('buildUserEmailKey')
            ->with($normalizedEmail)
            ->willReturn($cacheKey);
        $this->cache->expectGet(
            static function (
                string $actualCacheKey,
                callable $callback,
                ?float $beta
            ) use ($cacheKey, $user): UserInterface {
                self::assertSame($cacheKey, $actualCacheKey);
                self::assertIsCallable($callback);
                self::assertNull($beta);

                return $user;
            }
        );
        $this->documentManager->expects($this->once())
            ->method('contains')
            ->with($user)
            ->willReturn(true);
        $this->innerRepository
            ->expects($this->never())
            ->method('findByEmail');
        $this->innerRepository
            ->expects($this->never())
            ->method('findByEmailCaseInsensitive');

        $result = $this->repository->findByEmailCaseInsensitive($inputEmail);

        self::assertSame([$user], iterator_to_array($result));
    }

    public function testFindByEmailCaseInsensitiveDelegatesOnNormalizedCacheMiss(): void
    {
        $email = $this->faker->unique()->email();
        $inputEmail = '  ' . mb_strtoupper($email, 'UTF-8') . '  ';
        $normalizedEmail = mb_strtolower($email, 'UTF-8');
        $cacheKey = 'user.email.' . $this->faker->sha256();
        $item = $this->createMock(ItemInterface::class);
        $user = $this->createUserMock($this->faker->uuid(), $email);

        $this->cacheKeyBuilder
            ->expects($this->once())
            ->method('buildUserEmailKey')
            ->with($normalizedEmail)
            ->willReturn($cacheKey);
        $this->innerRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($normalizedEmail)
            ->willReturn(null);
        $this->innerRepository
            ->expects($this->once())
            ->method('findByEmailCaseInsensitive')
            ->with($normalizedEmail)
            ->willReturn(new UserCollection([$user]));
        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                'Cache miss - loading user by email',
                $this->callback(
                    static fn (array $context): bool => $context['cache_key'] === $cacheKey
                        && $context['operation'] === 'cache.miss'
                )
            );
        $item->expects($this->never())->method('expiresAfter');
        $item->expects($this->never())->method('tag');
        $this->cache->expectGet(
            static function (
                string $actualCacheKey,
                callable $callback,
                ?float $beta
            ) use ($cacheKey, $item): null {
                self::assertSame($cacheKey, $actualCacheKey);
                self::assertNull($beta);

                $save = true;
                $result = $callback($item, $save);

                self::assertFalse($save);

                return $result;
            }
        );
        $this->documentManager->expects($this->never())->method('contains');

        $result = $this->repository->findByEmailCaseInsensitive($inputEmail);

        self::assertSame([$user], iterator_to_array($result));
    }

    public function testDeleteBatchDelegatesToInnerRepository(): void
    {
        [$users, $hashesByEmail, $expectedTags] = $this->createBatchWriteFixtures();

        $this->expectBatchWrite('deleteBatch', $users, $hashesByEmail, $expectedTags);

        $this->repository->deleteBatch($users);
    }

    public function testSaveBatchIgnoresNonUserEntriesDuringInvalidation(): void
    {
        $user = $this->createUserMock($this->faker->uuid(), $this->faker->email());
        $hash = $this->faker->sha256();
        $users = new UserCollection([new \stdClass(), $user]);

        $this->innerRepository
            ->expects($this->once())
            ->method('saveBatch')
            ->with($users);
        $this->expectHashEmails([$user->getEmail() => $hash]);
        $this->expectInvalidateTags([
            'user',
            'user.collection',
            'user.' . $user->getId(),
            'user.email.' . $hash,
        ]);

        $this->repository->saveBatch($users);
    }

    public function testSaveLogsWarningWhenInvalidationFails(): void
    {
        $user = $this->createUserMock($this->faker->uuid(), $this->faker->email());
        $hash = $this->faker->sha256();

        $this->expectSaveDelegation($user);
        $this->expectHashEmail($user->getEmail(), $hash);
        $this->expectInvalidateTagsFailure($this->singleUserTags($user, $hash));
        $this->expectInvalidationWarning('Failed to invalidate cache after user write', 'save');

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
        $this->expectInvalidationWarning('Failed to invalidate cache after deleteAll');

        $this->repository->deleteAll();
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
     * @param list<string> $hashedEmails
     */
    private function expectSaveWithPreviousEmail(
        UserInterface $user,
        string $oldEmail,
        string $oldHash,
        string $newEmail,
        string $newHash,
        array &$hashedEmails
    ): void {
        $this->expectSaveDelegation($user);
        $this->documentManager
            ->expects($this->once())
            ->method('contains')
            ->with($user)
            ->willReturn(true);
        $this->expectOriginalEmail($user, $oldEmail);
        $this->expectHashEmailsForSave($oldEmail, $oldHash, $newEmail, $newHash, $hashedEmails);
        $this->expectInvalidateTagsCanonicalizing([
            'user',
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

    /**
     * @param list<string> $hashedEmails
     */
    private function expectHashEmailsForSave(
        string $oldEmail,
        string $oldHash,
        string $newEmail,
        string $newHash,
        array &$hashedEmails
    ): void {
        $this->cacheKeyBuilder
            ->method('hashEmail')
            ->willReturnCallback(
                static function (string $email) use (
                    $oldEmail,
                    $oldHash,
                    $newEmail,
                    $newHash,
                    &$hashedEmails
                ): string {
                    $hashedEmails[] = $email;

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

                throw CacheOperationFailedException::invalidationFailed();
            }
        );
    }

    private function expectInvalidationWarning(
        string $message,
        ?string $writeOperation = null
    ): void {
        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with(
                $message,
                $this->callback(
                    static fn (array $context): bool => isset($context['error'])
                        && $context['operation'] === 'cache.invalidation.error'
                        && ($writeOperation === null
                            || $context['write_operation'] === $writeOperation)
                )
            );
    }
}
