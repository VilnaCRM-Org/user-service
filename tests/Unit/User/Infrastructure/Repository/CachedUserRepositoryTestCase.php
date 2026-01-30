<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Repository;

use App\Shared\Infrastructure\Cache\CacheKeyBuilder;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Repository\CachedUserRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

abstract class CachedUserRepositoryTestCase extends UnitTestCase
{
    protected UserRepositoryInterface&MockObject $innerRepository;
    protected TagAwareCacheInterface&MockObject $cache;
    protected CacheKeyBuilder&MockObject $cacheKeyBuilder;
    protected LoggerInterface&MockObject $logger;
    protected DocumentManager&MockObject $documentManager;
    protected CachedUserRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->innerRepository = $this->createMock(UserRepositoryInterface::class);
        $this->cache = $this->createMock(TagAwareCacheInterface::class);
        $this->cacheKeyBuilder = $this->createMock(CacheKeyBuilder::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->documentManager = $this->createMock(DocumentManager::class);

        $this->repository = new CachedUserRepository(
            $this->innerRepository,
            $this->cache,
            $this->cacheKeyBuilder,
            $this->logger,
            $this->documentManager
        );
    }

    protected function createUserMock(string $id, ?string $email = null): UserInterface&MockObject
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getId')->willReturn($id);
        $user->method('getEmail')->willReturn($email ?? $this->faker->email());

        return $user;
    }
}
