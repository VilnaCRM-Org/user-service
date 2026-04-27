<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Repository;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Collection\RecoveryCodeCollection;
use App\User\Domain\Entity\RecoveryCode;
use App\User\Infrastructure\Repository\MongoDBRecoveryCodeRepository;
use DateTimeImmutable;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Query\Builder;
use Doctrine\ODM\MongoDB\Query\Query;
use PHPUnit\Framework\MockObject\MockObject;

final class MongoDBRecoveryCodeRepositoryTest extends UnitTestCase
{
    private DocumentManager|MockObject $documentManager;
    private ManagerRegistry|MockObject $registry;
    private MongoDBRecoveryCodeRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->documentManager = $this->createMock(DocumentManager::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->registry
            ->expects($this->atLeastOnce())
            ->method('getManagerForClass')
            ->with(RecoveryCode::class)
            ->willReturn($this->documentManager);

        $this->repository = new MongoDBRecoveryCodeRepository(
            $this->documentManager,
            $this->registry
        );
    }

    public function testSave(): void
    {
        $recoveryCode = $this->createRecoveryCode();

        $this->documentManager->expects($this->once())
            ->method('persist')
            ->with($recoveryCode);
        $this->documentManager->expects($this->once())
            ->method('flush');

        $this->repository->save($recoveryCode);
    }

    public function testSaveAllFlushesOnce(): void
    {
        $code1 = $this->createRecoveryCode();
        $code2 = $this->createRecoveryCode();

        $this->documentManager->expects($this->exactly(2))
            ->method('persist')
            ->with(
                $this->callback(
                    static fn (RecoveryCode $code): bool => in_array($code, [$code1, $code2], true)
                )
            );
        $this->documentManager->expects($this->once())
            ->method('flush');

        $this->repository->saveAll($code1, $code2);
    }

    public function testFindById(): void
    {
        $id = $this->faker->uuid();
        $expectedRecoveryCode = $this->createRecoveryCode();
        $repositoryClass = MongoDBRecoveryCodeRepository::class;
        $repository = $this->getMockBuilder($repositoryClass)
            ->setConstructorArgs([$this->documentManager, $this->registry])
            ->onlyMethods(['find'])
            ->getMock();

        $repository->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($expectedRecoveryCode);

        $this->assertSame($expectedRecoveryCode, $repository->findById($id));
    }

    public function testFindByUserId(): void
    {
        $userId = $this->faker->uuid();
        $code1 = $this->createRecoveryCode();
        $code2 = $this->createRecoveryCode();
        $repositoryClass = MongoDBRecoveryCodeRepository::class;
        $repository = $this->getMockBuilder($repositoryClass)
            ->setConstructorArgs([$this->documentManager, $this->registry])
            ->onlyMethods(['findBy'])
            ->getMock();

        $repository->expects($this->once())
            ->method('findBy')
            ->with(['userId' => $userId])
            ->willReturn([$code1, $code2]);

        $result = $repository->findByUserId($userId);
        $this->assertInstanceOf(RecoveryCodeCollection::class, $result);
        $this->assertCount(2, $result);
    }

    public function testCountUnusedByUserId(): void
    {
        $userId = $this->faker->uuid();
        $repository = $this->createRepositoryWithCountUnusedResult($userId, 4);

        $this->assertSame(4, $repository->countUnusedByUserId($userId));
    }

    public function testCountUnusedByUserIdReturnsZeroForUnexpectedResult(): void
    {
        $userId = $this->faker->uuid();
        $repository = $this->createRepositoryWithCountUnusedResult($userId, '4');

        $this->assertSame(0, $repository->countUnusedByUserId($userId));
    }

    public function testDelete(): void
    {
        $recoveryCode = $this->createRecoveryCode();

        $this->documentManager->expects($this->once())
            ->method('remove')
            ->with($recoveryCode);
        $this->documentManager->expects($this->once())
            ->method('flush');

        $this->repository->delete($recoveryCode);
    }

    public function testMarkAsUsedIfUnusedReturnsTrueWhenUpdateResultIsPositiveInt(): void
    {
        $repository = $this->createRepositoryWithMarkAsUsedResult(1);

        $this->assertTrue(
            $repository->markAsUsedIfUnused($this->faker->uuid(), new DateTimeImmutable())
        );
    }

    public function testMarkAsUsedIfUnusedSynchronizesManagedRecoveryCode(): void
    {
        $id = $this->faker->uuid();
        $usedAt = new DateTimeImmutable();
        $recoveryCode = new RecoveryCode($id, $this->faker->uuid(), 'ab12-cd34');
        $repository = $this->createRepositoryWithMarkAsUsedResult(1, $recoveryCode);

        $this->assertTrue($repository->markAsUsedIfUnused($id, $usedAt));
        $this->assertTrue($recoveryCode->isUsed());
        $this->assertSame($usedAt, $recoveryCode->getUsedAt());
    }

    public function testMarkAsUsedIfUnusedReturnsFalseWhenUpdateResultIsZero(): void
    {
        $repository = $this->createRepositoryWithMarkAsUsedResult(0);

        $this->assertFalse(
            $repository->markAsUsedIfUnused($this->faker->uuid(), new DateTimeImmutable())
        );
    }

    public function testMarkAsUsedIfUnusedReturnsFalseWhenModifiedCountIsNotInt(): void
    {
        $repository = $this->createRepositoryWithMarkAsUsedResult(new class() {
            public function getModifiedCount(): string
            {
                return '1';
            }
        });

        $this->assertFalse(
            $repository->markAsUsedIfUnused($this->faker->uuid(), new DateTimeImmutable())
        );
    }

    public function testMarkAsUsedIfUnusedReturnsFalseWhenResultHasNoModifiedCountMethod(): void
    {
        $repository = $this->createRepositoryWithMarkAsUsedResult(new \stdClass());

        $this->assertFalse(
            $repository->markAsUsedIfUnused($this->faker->uuid(), new DateTimeImmutable())
        );
    }

    public function testDeleteByUserId(): void
    {
        $userId = $this->faker->uuid();
        $repository = $this->createRepositoryWithDeleteByUserIdResult($userId, 2);
        $this->documentManager
            ->expects($this->once())
            ->method('clear')
            ->with(RecoveryCode::class);

        $result = $repository->deleteByUserId($userId);

        $this->assertSame(2, $result);
    }

    public function testDeleteByUserIdDoesNotClearWhenNothingWasDeleted(): void
    {
        $userId = $this->faker->uuid();
        $repository = $this->createRepositoryWithDeleteByUserIdResult($userId, 0);
        $this->documentManager
            ->expects($this->never())
            ->method('clear');

        $this->assertSame(0, $repository->deleteByUserId($userId));
    }

    public function testDeleteByUserIdUsesDeletedCountObject(): void
    {
        $userId = $this->faker->uuid();
        $repository = $this->createRepositoryWithDeleteByUserIdResult(
            $userId,
            new class() {
                public function getDeletedCount(): int
                {
                    return 2;
                }
            }
        );

        $this->documentManager
            ->expects($this->once())
            ->method('clear')
            ->with(RecoveryCode::class);

        $this->assertSame(2, $repository->deleteByUserId($userId));
    }

    public function testDeleteByUserIdReturnsZeroForUnexpectedDeleteResults(): void
    {
        $userId = $this->faker->uuid();
        $invalidDeletedCount = new class() {
            public function getDeletedCount(): string
            {
                return '2';
            }
        };

        $this->documentManager
            ->expects($this->never())
            ->method('clear');

        $repository = $this->createRepositoryWithDeleteByUserIdResult(
            $userId,
            $invalidDeletedCount
        );
        $this->assertSame(0, $repository->deleteByUserId($userId));

        $repository = $this->createRepositoryWithDeleteByUserIdResult($userId, new \stdClass());
        $this->assertSame(0, $repository->deleteByUserId($userId));
    }

    private function createRecoveryCode(): RecoveryCode
    {
        return new RecoveryCode(
            $this->faker->uuid(),
            $this->faker->uuid(),
            strtolower($this->faker->bothify('????-????'))
        );
    }

    private function createRepositoryWithMarkAsUsedResult(
        mixed $updateResult,
        ?RecoveryCode $managedRecoveryCode = null
    ): MongoDBRecoveryCodeRepository {
        $queryBuilder = $this->createMock(Builder::class);
        $query = $this->createMock(Query::class);
        $repository = $this->createRepositoryWithQueryBuilderAndFind($queryBuilder);

        $this->stubMarkAsUsedQuery($queryBuilder, $query, $updateResult);
        $repository->method('find')->willReturn($managedRecoveryCode);

        return $repository;
    }

    private function stubMarkAsUsedQuery(
        Builder $queryBuilder,
        Query $query,
        mixed $updateResult
    ): void {
        $queryBuilder->method('updateOne')->willReturnSelf();
        $queryBuilder->method('field')->willReturnSelf();
        $queryBuilder->method('equals')->willReturnSelf();
        $queryBuilder->method('set')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);
        $query->method('execute')->willReturn($updateResult);
    }

    private function createRepositoryWithDeleteByUserIdResult(
        string $expectedUserId,
        mixed $deleteResult
    ): MongoDBRecoveryCodeRepository {
        $queryBuilder = $this->createMock(Builder::class);
        $query = $this->createMock(Query::class);
        $repository = $this->createRepositoryWithQueryBuilder($queryBuilder);

        $this->expectDeleteByUserIdQuery($queryBuilder, $query, $expectedUserId, $deleteResult);

        return $repository;
    }

    private function expectDeleteByUserIdQuery(
        Builder $queryBuilder,
        Query $query,
        string $expectedUserId,
        mixed $deleteResult
    ): void {
        $queryBuilder->expects($this->once())->method('remove')->willReturnSelf();
        $queryBuilder
            ->expects($this->once())
            ->method('field')
            ->with('userId')
            ->willReturnSelf();
        $queryBuilder
            ->expects($this->once())
            ->method('equals')
            ->with($expectedUserId)
            ->willReturnSelf();
        $queryBuilder->expects($this->once())->method('getQuery')->willReturn($query);
        $query->expects($this->once())->method('execute')->willReturn($deleteResult);
    }

    private function createRepositoryWithCountUnusedResult(
        string $expectedUserId,
        mixed $countResult
    ): MongoDBRecoveryCodeRepository {
        $queryBuilder = $this->createMock(Builder::class);
        $query = $this->createMock(Query::class);
        $repository = $this->createRepositoryWithQueryBuilder($queryBuilder);

        $this->expectCountUnusedFields($queryBuilder, $expectedUserId);
        $queryBuilder->expects($this->once())->method('count')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('getQuery')->willReturn($query);
        $query->expects($this->once())->method('execute')->willReturn($countResult);

        return $repository;
    }

    private function expectCountUnusedFields(Builder $queryBuilder, string $expectedUserId): void
    {
        $queryBuilder
            ->expects($this->exactly(2))
            ->method('field')
            ->willReturnCallback(
                static function (string $field) use ($queryBuilder): Builder {
                    self::assertContains($field, ['userId', 'usedAt']);

                    return $queryBuilder;
                }
            );
        $queryBuilder
            ->expects($this->exactly(2))
            ->method('equals')
            ->willReturnCallback(
                static function (mixed $value) use ($expectedUserId, $queryBuilder): Builder {
                    self::assertContains($value, [$expectedUserId, null]);

                    return $queryBuilder;
                }
            );
    }

    private function createRepositoryWithQueryBuilder(
        Builder $queryBuilder
    ): MongoDBRecoveryCodeRepository {
        $repository = $this->getMockBuilder(MongoDBRecoveryCodeRepository::class)
            ->setConstructorArgs([$this->documentManager, $this->registry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $repository->method('createQueryBuilder')->willReturn($queryBuilder);

        return $repository;
    }

    private function createRepositoryWithQueryBuilderAndFind(
        Builder $queryBuilder
    ): MongoDBRecoveryCodeRepository {
        $repository = $this->getMockBuilder(MongoDBRecoveryCodeRepository::class)
            ->setConstructorArgs([$this->documentManager, $this->registry])
            ->onlyMethods(['createQueryBuilder', 'find'])
            ->getMock();

        $repository->method('createQueryBuilder')->willReturn($queryBuilder);

        return $repository;
    }
}
