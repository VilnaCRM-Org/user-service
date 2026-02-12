<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Repository;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\RecoveryCode;
use App\User\Infrastructure\Repository\MongoDBRecoveryCodeRepository;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\ODM\MongoDB\DocumentManager;
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

    public function testDeleteByUserId(): void
    {
        $userId = $this->faker->uuid();
        $codes = [
            $this->createRecoveryCode(),
            $this->createRecoveryCode(),
        ];

        $repositoryClass = MongoDBRecoveryCodeRepository::class;
        $repository = $this->getMockBuilder($repositoryClass)
            ->setConstructorArgs(
                [$this->documentManager, $this->registry]
            )
            ->onlyMethods(['findByUserId'])
            ->getMock();

        $repository->expects($this->once())
            ->method('findByUserId')
            ->with($userId)
            ->willReturn($codes);

        $this->documentManager
            ->expects($this->exactly(2))
            ->method('remove');

        $this->documentManager
            ->expects($this->once())
            ->method('flush');

        $result = $repository->deleteByUserId($userId);
        $this->assertSame(2, $result);
    }

    private function createRecoveryCode(): RecoveryCode
    {
        return new RecoveryCode(
            $this->faker->uuid(),
            $this->faker->uuid(),
            strtolower($this->faker->bothify('????-????'))
        );
    }
}
