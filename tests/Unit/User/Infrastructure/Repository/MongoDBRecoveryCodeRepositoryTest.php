<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Repository;

use App\Tests\Unit\UnitTestCase;
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
        $expectedRecoveryCodes = [
            $this->createRecoveryCode(),
            $this->createRecoveryCode(),
        ];
        $repositoryClass = MongoDBRecoveryCodeRepository::class;
        $repository = $this->getMockBuilder($repositoryClass)
            ->setConstructorArgs([$this->documentManager, $this->registry])
            ->onlyMethods(['findBy'])
            ->getMock();

        $repository->expects($this->once())
            ->method('findBy')
            ->with(['userId' => $userId])
            ->willReturn($expectedRecoveryCodes);

        $this->assertSame($expectedRecoveryCodes, $repository->findByUserId($userId));
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
        $codes = [$this->createRecoveryCode(), $this->createRecoveryCode()];
        $repository = $this->createMockRepositoryWithFindByUserId($userId, $codes);
        $this->documentManager
            ->expects($this->exactly(2))
            ->method('remove');
        $this->documentManager
            ->expects($this->once())
            ->method('flush');
        $result = $repository->deleteByUserId($userId);
        $this->assertSame(2, $result);
    }

    /**
     * @param array<RecoveryCode> $codes
     */
    private function createMockRepositoryWithFindByUserId(
        string $userId,
        array $codes
    ): MongoDBRecoveryCodeRepository {
        $repository = $this->getMockBuilder(MongoDBRecoveryCodeRepository::class)
            ->setConstructorArgs([$this->documentManager, $this->registry])
            ->onlyMethods(['findByUserId'])
            ->getMock();
        $repository->expects($this->once())
            ->method('findByUserId')
            ->with($userId)
            ->willReturn($codes);

        return $repository;
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
        $repository = $this->getMockBuilder(MongoDBRecoveryCodeRepository::class)
            ->setConstructorArgs([$this->documentManager, $this->registry])
            ->onlyMethods(['createQueryBuilder', 'find'])
            ->getMock();

        $queryBuilder = $this->createMock(Builder::class);
        $query = $this->createMock(Query::class);

        $queryBuilder->method('updateOne')->willReturnSelf();
        $queryBuilder->method('field')->willReturnSelf();
        $queryBuilder->method('equals')->willReturnSelf();
        $queryBuilder->method('set')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);

        $query->method('execute')->willReturn($updateResult);

        $repository->method('createQueryBuilder')->willReturn($queryBuilder);
        $repository->method('find')->willReturn($managedRecoveryCode);

        return $repository;
    }
}
