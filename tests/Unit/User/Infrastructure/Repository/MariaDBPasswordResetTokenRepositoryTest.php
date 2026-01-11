<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Repository;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\PasswordResetToken;
use App\User\Domain\Entity\PasswordResetTokenInterface;
use App\User\Infrastructure\Repository\MariaDBPasswordResetTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Persisters\Entity\EntityPersister;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;

final class MariaDBPasswordResetTokenRepositoryTest extends UnitTestCase
{
    private EntityManagerInterface|MockObject $entityManager;
    private ManagerRegistry|MockObject $registry;
    private MariaDBPasswordResetTokenRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->repository = new MariaDBPasswordResetTokenRepository(
            $this->entityManager,
            $this->registry
        );
    }

    public function testSave(): void
    {
        $token = $this->createMock(PasswordResetTokenInterface::class);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($token);

        $this->entityManager->expects($this->once())
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

        $repositoryClass = MariaDBPasswordResetTokenRepository::class;
        $repository = $this->getMockBuilder($repositoryClass)
            ->setConstructorArgs([$this->entityManager, $this->registry])
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

        $repositoryClass = MariaDBPasswordResetTokenRepository::class;
        $repository = $this->getMockBuilder($repositoryClass)
            ->setConstructorArgs([$this->entityManager, $this->registry])
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

        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($token);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->repository->delete($token);
    }

    public function testSaveBatch(): void
    {
        $token1 = $this->createMock(PasswordResetTokenInterface::class);
        $token2 = $this->createMock(PasswordResetTokenInterface::class);
        $tokens = [$token1, $token2];

        $expectedTokens = [$token1, $token2];
        $callIndex = 0;

        $this->entityManager->expects($this->exactly(2))
            ->method('persist')
            ->willReturnCallback(
                function ($token) use ($expectedTokens, &$callIndex): void {
                    $this->assertSame($expectedTokens[$callIndex], $token);
                    $callIndex++;
                }
            );

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->repository->saveBatch($tokens);
    }

    public function testSaveBatchWithEmptyArray(): void
    {
        $this->entityManager->expects($this->never())
            ->method('persist');

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->repository->saveBatch([]);
    }

    /**
     * @param array<string, string> $criteria
     * @param array<string, string>|null $orderBy
     */
    private function setupFindOneByExpectation(
        array $criteria,
        ?PasswordResetTokenInterface $expectedResult,
        ?array $orderBy = null
    ): void {
        $mocks = $this->createDoctrineManagerMocks();
        $this->setupEntityManagerExpectations($mocks);
        $this->setupPersisterExpectations(
            $mocks,
            $criteria,
            $expectedResult,
            $orderBy
        );
        $this->setupRegistryExpectation();
    }

    /**
     * @return array<string, object>
     */
    private function createDoctrineManagerMocks(): array
    {
        $unitOfWork = $this->createMock(UnitOfWork::class);
        $persister = $this->createMock(EntityPersister::class);
        $metadataMock = $this->createMock(ClassMetadata::class);
        $metadataMock->name = PasswordResetToken::class;

        return [
            'unitOfWork' => $unitOfWork,
            'persister' => $persister,
            'metadata' => $metadataMock,
        ];
    }

    /**
     * @param array<string, object> $mocks
     */
    private function setupEntityManagerExpectations(array $mocks): void
    {
        $this->entityManager->expects($this->once())
            ->method('getClassMetadata')
            ->willReturn($mocks['metadata']);

        $this->entityManager->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($mocks['unitOfWork']);
    }

    /**
     * @param array<string, object> $mocks
     * @param array<string, string> $criteria
     */
    private function setupPersisterExpectations(
        array $mocks,
        array $criteria,
        ?PasswordResetTokenInterface $expectedResult,
        ?array $orderBy
    ): void {
        $mocks['unitOfWork']->expects($this->once())
            ->method('getEntityPersister')
            ->willReturn($mocks['persister']);

        $persisterMethod = $mocks['persister']
            ->expects($this->once())
            ->method('load');
        $persisterMethod->with(
            $criteria,
            $this->anything(),
            $this->anything(),
            [],
            $orderBy
        )
            ->willReturn($expectedResult);
    }

    private function setupRegistryExpectation(): void
    {
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);
    }
}
