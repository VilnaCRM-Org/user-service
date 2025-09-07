<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Repository;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\PasswordResetToken;
use App\User\Domain\Entity\PasswordResetTokenInterface;
use App\User\Infrastructure\Repository\MariaDBPasswordResetTokenRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Statement;
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
    private Connection|MockObject $connection;
    private MariaDBPasswordResetTokenRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->connection = $this->createMock(Connection::class);

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

        $this->setupFindOneByExpectation(['tokenValue' => $tokenValue], $expectedToken);

        $result = $this->repository->findByToken($tokenValue);

        $this->assertSame($expectedToken, $result);
    }

    public function testFindByUserID(): void
    {
        $userID = $this->faker->uuid();
        $expectedToken = $this->createMock(PasswordResetTokenInterface::class);

        // Create a mock repository that overrides just the findOneBy method
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

        // Create a mock repository that overrides just the findOneBy method
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

    public function testCountRecentRequestsByEmail(): void
    {
        $email = $this->faker->email();
        $since = new \DateTimeImmutable('-1 hour');
        $expectedCount = $this->faker->numberBetween(0, 10);

        $repository = $this->createRepositoryMock();
        $statement = $this->setupDatabaseMocks($repository, $expectedCount);
        $this->setupStatementBindings($statement, $email, $since);

        $count = $repository->countRecentRequestsByEmail($email, $since);

        $this->assertSame($expectedCount, $count);
    }

    private function createRepositoryMock(): MariaDBPasswordResetTokenRepository
    {
        $repositoryClass = MariaDBPasswordResetTokenRepository::class;
        $repository = $this->getMockBuilder($repositoryClass)
            ->setConstructorArgs([$this->entityManager, $this->registry])
            ->onlyMethods(['getEntityManager'])
            ->getMock();

        $repository->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($this->entityManager);

        return $repository;
    }

    private function setupDatabaseMocks(
        MariaDBPasswordResetTokenRepository $repository,
        int $expectedCount
    ): Statement {
        $statement = $this->createMock(Statement::class);
        $result = $this->createMock(Result::class);

        $this->entityManager->expects($this->once())
            ->method('getConnection')
            ->willReturn($this->connection);

        $this->connection->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('SELECT COUNT(prt.token_value)'))
            ->willReturn($statement);

        $statement->expects($this->once())
            ->method('executeQuery')
            ->willReturn($result);

        $result->expects($this->once())
            ->method('fetchOne')
            ->willReturn((string) $expectedCount);

        return $statement;
    }

    private function setupStatementBindings(
        Statement $statement,
        string $email,
        \DateTimeImmutable $since
    ): void {
        $statement->expects($this->exactly(2))
            ->method('bindValue')
            ->withConsecutive(
                ['email', $email],
                ['since', $since->format('Y-m-d H:i:s')]
            );
    }

    public function testCountRecentRequestsByEmailReturnsZero(): void
    {
        $email = $this->faker->email();
        $since = new \DateTimeImmutable('-1 hour');

        $repository = $this->createRepositoryMock();
        $this->setupDatabaseMocksForZeroResult($repository);

        $count = $repository->countRecentRequestsByEmail($email, $since);

        $this->assertSame(0, $count);
    }

    private function setupDatabaseMocksForZeroResult(
        MariaDBPasswordResetTokenRepository $repository
    ): void {
        $statement = $this->createMock(Statement::class);
        $result = $this->createMock(Result::class);

        $this->entityManager->expects($this->once())
            ->method('getConnection')
            ->willReturn($this->connection);

        $this->connection->expects($this->once())
            ->method('prepare')
            ->willReturn($statement);

        $statement->expects($this->exactly(2))
            ->method('bindValue');

        $statement->expects($this->once())
            ->method('executeQuery')
            ->willReturn($result);

        $result->expects($this->once())
            ->method('fetchOne')
            ->willReturn('0');
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
        $this->setupPersisterExpectations($mocks, $criteria, $expectedResult, $orderBy);
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

        $persisterMethod = $mocks['persister']->expects($this->once())->method('load');
        $persisterMethod->with($criteria, $this->anything(), $this->anything(), [], $orderBy)
            ->willReturn($expectedResult);
    }

    private function setupRegistryExpectation(): void
    {
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);
    }
}
