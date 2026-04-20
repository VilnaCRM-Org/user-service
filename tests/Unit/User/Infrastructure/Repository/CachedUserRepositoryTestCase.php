<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Repository;

use App\Shared\Infrastructure\Cache\CacheKeyBuilder;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Repository\CachedUserRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\UnitOfWork;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

abstract class CachedUserRepositoryTestCase extends UnitTestCase
{
    protected UserRepositoryInterface&MockObject $innerRepository;
    protected TagAwareCacheSpy $cache;
    protected CacheKeyBuilder&MockObject $cacheKeyBuilder;
    protected LoggerInterface&MockObject $logger;
    protected DocumentManager&MockObject $documentManager;
    protected UnitOfWork&MockObject $unitOfWork;
    protected CachedUserRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->innerRepository = $this->createMock(UserRepositoryInterface::class);
        $this->cache = new TagAwareCacheSpy();
        $this->cacheKeyBuilder = $this->createMock(CacheKeyBuilder::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->documentManager = $this->createMock(DocumentManager::class);
        $this->unitOfWork = $this->createMock(UnitOfWork::class);
        $this->documentManager->method('getUnitOfWork')->willReturn($this->unitOfWork);

        $this->repository = new CachedUserRepository(
            $this->innerRepository,
            $this->cache,
            $this->cacheKeyBuilder,
            $this->logger,
            $this->documentManager
        );
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->cache->assertExpectationsMet();

        parent::tearDown();
    }

    protected function createUserMock(string $id, ?string $email = null): UserInterface&MockObject
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getId')->willReturn($id);
        $user->method('getEmail')->willReturn($email ?? $this->faker->email());

        return $user;
    }

    protected function expectHashEmail(string $email, string $hash): void
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
    protected function expectHashEmails(array $hashesByEmail): void
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
    protected function expectInvalidateTags(array $expectedTags): void
    {
        $this->cache->expectInvalidateTags(
            static function (array $tags) use ($expectedTags): bool {
                self::assertSame($expectedTags, $tags);

                return true;
            }
        );
    }

    /**
     * @param list<string> $expectedTags
     */
    protected function expectInvalidateTagsCanonicalizing(array $expectedTags): void
    {
        $this->cache->expectInvalidateTags(
            static function (array $tags) use ($expectedTags): bool {
                self::assertEqualsCanonicalizing($expectedTags, $tags);

                return true;
            }
        );
    }

    /**
     * @return list<string>
     */
    protected function singleUserTags(UserInterface $user, string $hash): array
    {
        return [
            'user',
            'user.collection',
            'user.' . $user->getId(),
            'user.email.' . $hash,
        ];
    }
}
