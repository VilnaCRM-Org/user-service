<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Repository;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\PasswordResetToken;
use App\User\Domain\Entity\PasswordResetTokenInterface;
use App\User\Infrastructure\Repository\MongoDBPasswordResetTokenRepository;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Persisters\DocumentPersister;
use Doctrine\ODM\MongoDB\UnitOfWork;
use PHPUnit\Framework\MockObject\MockObject;

final class MongoDBPasswordResetTokenRepositoryTest extends UnitTestCase
{
    private DocumentManager|MockObject $documentManager;
    private ManagerRegistry|MockObject $registry;
    private MongoDBPasswordResetTokenRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->documentManager = $this->createMock(DocumentManager::class);
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->repository = new MongoDBPasswordResetTokenRepository(
            $this->documentManager,
            $this->registry
        );
    }

    public function testSave(): void
    {
        $token = $this->createMock(PasswordResetTokenInterface::class);

        $this->documentManager->expects($this->once())
            ->method('persist')
            ->with($token);

        $this->documentManager->expects($this->once())
            ->method('flush');

        $this->repository->save($token);
    }

    public function testFindByToken(): void
    {
        $tokenValue = $this->faker->uuid();
        $expectedToken = $this->createMock(PasswordResetTokenInterface::class);

        $this->setupFindOneByExpectation(
            ['tokenValue' => $tokenValue],
            $expectedToken
        );

        $result = $this->repository->findByToken($tokenValue);

        $this->assertSame($expectedToken, $result);
    }

    public function testFindByUserID(): void
    {
        $userID = $this->faker->uuid();
        $expectedToken = $this->createMock(PasswordResetTokenInterface::class);

        $repositoryClass = MongoDBPasswordResetTokenRepository::class;
        $repository = $this->getMockBuilder($repositoryClass)
            ->setConstructorArgs([$this->documentManager, $this->registry])
            ->onlyMethods(['findOneBy'])
            ->getMock();

        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(['userID' => $userID], ['createdAt' => 'DESC'])
            ->willReturn($expectedToken);

        $result = $repository->findByUserID($userID);

        $this->assertSame($expectedToken, $result);
    }

    public function testFindByUserIDReturnsNull(): void
    {
        $userID = $this->faker->uuid();

        $repositoryClass = MongoDBPasswordResetTokenRepository::class;
        $repository = $this->getMockBuilder($repositoryClass)
            ->setConstructorArgs([$this->documentManager, $this->registry])
            ->onlyMethods(['findOneBy'])
            ->getMock();

        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(['userID' => $userID], ['createdAt' => 'DESC'])
            ->willReturn(null);

        $result = $repository->findByUserID($userID);

        $this->assertNull($result);
    }

    public function testDelete(): void
    {
        $token = $this->createMock(PasswordResetTokenInterface::class);

        $this->documentManager->expects($this->once())
            ->method('remove')
            ->with($token);

        $this->documentManager->expects($this->once())
            ->method('flush');

        $this->repository->delete($token);
    }

    public function testSaveBatch(): void
    {
        $tokens = [
            $this->createMock(PasswordResetTokenInterface::class),
            $this->createMock(PasswordResetTokenInterface::class),
        ];

        $this->documentManager->expects($this->exactly(2))
            ->method('persist');

        $this->documentManager->expects($this->once())
            ->method('flush');

        $this->repository->saveBatch($tokens);
    }

    private function setupFindOneByExpectation(
        array $criteria,
        ?PasswordResetTokenInterface $result
    ): void {
        $repositoryClass = MongoDBPasswordResetTokenRepository::class;
        $repository = $this->getMockBuilder($repositoryClass)
            ->setConstructorArgs([$this->documentManager, $this->registry])
            ->onlyMethods(['findOneBy'])
            ->getMock();

        $repository->expects($this->once())
            ->method('findOneBy')
            ->with($criteria)
            ->willReturn($result);

        $this->repository = $repository;
    }
}
