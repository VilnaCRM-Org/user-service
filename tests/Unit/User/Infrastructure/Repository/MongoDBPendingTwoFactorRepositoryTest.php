<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Repository;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\PendingTwoFactor;
use App\User\Infrastructure\Repository\MongoDBPendingTwoFactorRepository;
use DateTimeImmutable;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Query\Builder;
use Doctrine\ODM\MongoDB\Query\Query;
use PHPUnit\Framework\MockObject\MockObject;

final class MongoDBPendingTwoFactorRepositoryTest extends UnitTestCase
{
    private DocumentManager|MockObject $documentManager;
    private ManagerRegistry|MockObject $registry;
    private MongoDBPendingTwoFactorRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->documentManager = $this->createMock(DocumentManager::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->registry
            ->expects($this->atLeastOnce())
            ->method('getManagerForClass')
            ->with(PendingTwoFactor::class)
            ->willReturn($this->documentManager);

        $this->repository = new MongoDBPendingTwoFactorRepository(
            $this->documentManager,
            $this->registry
        );
    }

    public function testSave(): void
    {
        $pendingTwoFactor = $this->createPendingTwoFactor();

        $this->documentManager->expects($this->once())
            ->method('persist')
            ->with($pendingTwoFactor);
        $this->documentManager->expects($this->once())
            ->method('flush');

        $this->repository->save($pendingTwoFactor);
    }

    public function testFindById(): void
    {
        $id = $this->faker->uuid();
        $expectedPending = $this->createPendingTwoFactor();
        $repositoryClass = MongoDBPendingTwoFactorRepository::class;
        $repository = $this->getMockBuilder($repositoryClass)
            ->setConstructorArgs([$this->documentManager, $this->registry])
            ->onlyMethods(['find'])
            ->getMock();

        $repository->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($expectedPending);

        $this->assertSame($expectedPending, $repository->findById($id));
    }

    public function testDelete(): void
    {
        $pendingTwoFactor = $this->createPendingTwoFactor();

        $this->documentManager->expects($this->once())
            ->method('remove')
            ->with($pendingTwoFactor);
        $this->documentManager->expects($this->once())
            ->method('flush');

        $this->repository->delete($pendingTwoFactor);
    }

    public function testConsumeIfActiveReturnsTrueWhenDeleteCountIsPositiveInt(): void
    {
        $repository = $this->createRepositoryWithConsumeResult(1);

        $this->assertTrue(
            $repository->consumeIfActive($this->faker->uuid(), new DateTimeImmutable())
        );
    }

    public function testConsumeIfActiveReturnsFalseWhenDeleteCountIsZero(): void
    {
        $repository = $this->createRepositoryWithConsumeResult(0);

        $this->assertFalse(
            $repository->consumeIfActive($this->faker->uuid(), new DateTimeImmutable())
        );
    }

    public function testConsumeIfActiveReturnsFalseWhenDeletedCountIsNotInt(): void
    {
        $repository = $this->createRepositoryWithConsumeResult(new class() {
            public function getDeletedCount(): string
            {
                return '1';
            }
        });

        $this->assertFalse(
            $repository->consumeIfActive($this->faker->uuid(), new DateTimeImmutable())
        );
    }

    public function testConsumeIfActiveReturnsFalseWhenResultHasNoDeletedCountMethod(): void
    {
        $repository = $this->createRepositoryWithConsumeResult(new \stdClass());

        $this->assertFalse(
            $repository->consumeIfActive($this->faker->uuid(), new DateTimeImmutable())
        );
    }

    private function createPendingTwoFactor(): PendingTwoFactor
    {
        return new PendingTwoFactor(
            $this->faker->uuid(),
            $this->faker->uuid(),
            new DateTimeImmutable()
        );
    }

    private function createRepositoryWithConsumeResult(
        mixed $consumeResult
    ): MongoDBPendingTwoFactorRepository {
        $repository = $this->getMockBuilder(MongoDBPendingTwoFactorRepository::class)
            ->setConstructorArgs([$this->documentManager, $this->registry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $queryBuilder = $this->createMock(Builder::class);
        $query = $this->createMock(Query::class);

        $queryBuilder->method('remove')->willReturnSelf();
        $queryBuilder->method('field')->willReturnSelf();
        $queryBuilder->method('equals')->willReturnSelf();
        $queryBuilder->method('gte')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);

        $query->method('execute')->willReturn($consumeResult);

        $repository->method('createQueryBuilder')->willReturn($queryBuilder);

        return $repository;
    }
}
