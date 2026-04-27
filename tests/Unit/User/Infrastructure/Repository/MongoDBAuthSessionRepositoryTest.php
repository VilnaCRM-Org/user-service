<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Repository;

use App\User\Domain\Collection\AuthSessionCollection;
use App\User\Domain\Entity\AuthSession;
use App\User\Infrastructure\Repository\MongoDBAuthSessionRepository;
use App\User\Infrastructure\Repository\MongoDBWriteResultCounter;
use DateTimeImmutable;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Query\Builder;
use Doctrine\ODM\MongoDB\Query\Query;
use PHPUnit\Framework\MockObject\MockObject;

final class MongoDBAuthSessionRepositoryTest extends MongoDBRepositoryTestCase
{
    private DocumentManager|MockObject $documentManager;
    private ManagerRegistry|MockObject $registry;
    private MongoDBWriteResultCounter $writeResultCounter;
    private MongoDBAuthSessionRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->documentManager = $this->createMock(DocumentManager::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->registry
            ->expects($this->atLeastOnce())
            ->method('getManagerForClass')
            ->with(AuthSession::class)
            ->willReturn($this->documentManager);
        $this->writeResultCounter = new MongoDBWriteResultCounter();

        $this->repository = new MongoDBAuthSessionRepository(
            $this->documentManager,
            $this->registry,
            $this->writeResultCounter
        );
    }

    public function testSave(): void
    {
        $session = $this->createAuthSession();

        $this->documentManager->expects($this->once())
            ->method('persist')
            ->with($session);
        $this->documentManager->expects($this->once())
            ->method('flush');

        $this->repository->save($session);
    }

    public function testFindById(): void
    {
        $id = $this->faker->uuid();
        $expectedSession = $this->createAuthSession();
        $repositoryClass = MongoDBAuthSessionRepository::class;
        $repository = $this->getMockBuilder($repositoryClass)
            ->setConstructorArgs([
                $this->documentManager,
                $this->registry,
                $this->writeResultCounter,
            ])
            ->onlyMethods(['find'])
            ->getMock();

        $repository->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($expectedSession);

        $this->assertSame($expectedSession, $repository->findById($id));
    }

    public function testFindByUserId(): void
    {
        $userId = $this->faker->uuid();
        $sessions = [$this->createAuthSession(), $this->createAuthSession()];

        $repository = $this->createRepositoryMock(
            MongoDBAuthSessionRepository::class,
            [$this->documentManager, $this->registry, $this->writeResultCounter],
            ['findBy']
        );

        $repository->expects($this->once())
            ->method('findBy')
            ->with(['userId' => $userId])
            ->willReturn($sessions);

        $result = $repository->findByUserId($userId);
        $this->assertInstanceOf(AuthSessionCollection::class, $result);
        $this->assertCount(2, $result);
    }

    public function testDelete(): void
    {
        $session = $this->createAuthSession();

        $this->documentManager->expects($this->once())
            ->method('remove')
            ->with($session);
        $this->documentManager->expects($this->once())
            ->method('flush');

        $this->repository->delete($session);
    }

    public function testRevokeOtherActiveByUserId(): void
    {
        $userId = $this->faker->uuid();
        $currentSessionId = $this->faker->uuid();
        $revokedAt = new DateTimeImmutable();
        $repository = $this->createRepositoryWithBulkRevokeResult(
            $userId,
            $currentSessionId,
            $revokedAt,
            3
        );

        $this->documentManager
            ->expects($this->once())
            ->method('clear')
            ->with(AuthSession::class);

        $this->assertSame(
            3,
            $repository->revokeOtherActiveByUserId($userId, $currentSessionId, $revokedAt)
        );
    }

    public function testRevokeOtherActiveByUserIdDoesNotClearWhenNothingWasModified(): void
    {
        $userId = $this->faker->uuid();
        $currentSessionId = $this->faker->uuid();
        $revokedAt = new DateTimeImmutable();
        $repository = $this->createRepositoryWithBulkRevokeResult(
            $userId,
            $currentSessionId,
            $revokedAt,
            0
        );

        $this->documentManager->expects($this->never())->method('clear');

        $this->assertSame(
            0,
            $repository->revokeOtherActiveByUserId($userId, $currentSessionId, $revokedAt)
        );
    }

    public function testRevokeOtherActiveByUserIdUsesModifiedCountObject(): void
    {
        $userId = $this->faker->uuid();
        $currentSessionId = $this->faker->uuid();
        $revokedAt = new DateTimeImmutable();
        $repository = $this->createRepositoryWithBulkRevokeResult(
            $userId,
            $currentSessionId,
            $revokedAt,
            $this->modifiedCountResult(2)
        );

        $this->documentManager
            ->expects($this->once())
            ->method('clear')
            ->with(AuthSession::class);

        $this->assertSame(
            2,
            $repository->revokeOtherActiveByUserId($userId, $currentSessionId, $revokedAt)
        );
    }

    public function testRevokeOtherActiveByUserIdReturnsZeroWhenModifiedCountIsNotInt(): void
    {
        $userId = $this->faker->uuid();
        $currentSessionId = $this->faker->uuid();
        $revokedAt = new DateTimeImmutable();
        $repository = $this->createRepositoryWithBulkRevokeResult(
            $userId,
            $currentSessionId,
            $revokedAt,
            $this->modifiedCountResult('3')
        );

        $this->assertSame(
            0,
            $repository->revokeOtherActiveByUserId($userId, $currentSessionId, $revokedAt)
        );
    }

    public function testRevokeOtherActiveByUserIdReturnsZeroWithoutModifiedCount(): void
    {
        $userId = $this->faker->uuid();
        $currentSessionId = $this->faker->uuid();
        $revokedAt = new DateTimeImmutable();
        $repository = $this->createRepositoryWithBulkRevokeResult(
            $userId,
            $currentSessionId,
            $revokedAt,
            new \stdClass()
        );

        $this->documentManager->expects($this->never())->method('clear');

        $this->assertSame(
            0,
            $repository->revokeOtherActiveByUserId($userId, $currentSessionId, $revokedAt)
        );
    }

    private function createAuthSession(): AuthSession
    {
        $createdAt = new DateTimeImmutable();

        return new AuthSession(
            $this->faker->uuid(),
            $this->faker->uuid(),
            $this->faker->ipv4(),
            $this->faker->userAgent(),
            $createdAt,
            $createdAt->modify('+1 hour'),
            false
        );
    }

    private function createRepositoryWithBulkRevokeResult(
        string $expectedUserId,
        string $expectedCurrentSessionId,
        DateTimeImmutable $expectedRevokedAt,
        mixed $updateResult
    ): MongoDBAuthSessionRepository {
        $queryBuilder = $this->createMock(Builder::class);
        $query = $this->createMock(Query::class);
        $repository = $this->createRepositoryMockWithQueryBuilder(
            MongoDBAuthSessionRepository::class,
            [$this->documentManager, $this->registry, $this->writeResultCounter],
            $queryBuilder
        );

        $queryBuilder->expects($this->once())->method('updateMany')->willReturnSelf();
        $this->expectBulkRevokeFields($queryBuilder, $expectedUserId);
        $this->expectBulkRevokeUpdate(
            $queryBuilder,
            $expectedCurrentSessionId,
            $expectedRevokedAt
        );
        $this->expectQueryResult($queryBuilder, $query, $updateResult);

        return $repository;
    }

    private function expectBulkRevokeFields(Builder $queryBuilder, string $expectedUserId): void
    {
        $queryBuilder
            ->expects($this->exactly(4))
            ->method('field')
            ->willReturnCallback(
                static function (string $field) use ($queryBuilder): Builder {
                    self::assertContains($field, ['userId', 'id', 'revokedAt']);

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

    private function expectBulkRevokeUpdate(
        Builder $queryBuilder,
        string $expectedCurrentSessionId,
        DateTimeImmutable $expectedRevokedAt
    ): void {
        $queryBuilder
            ->expects($this->once())
            ->method('notEqual')
            ->with($expectedCurrentSessionId)
            ->willReturnSelf();
        $queryBuilder
            ->expects($this->once())
            ->method('set')
            ->with($expectedRevokedAt)
            ->willReturnSelf();
    }

    private function expectQueryResult(Builder $queryBuilder, Query $query, mixed $result): void
    {
        $queryBuilder->expects($this->once())->method('getQuery')->willReturn($query);
        $query->expects($this->once())->method('execute')->willReturn($result);
    }

    private function modifiedCountResult(int|string $modifiedCount): object
    {
        return new WriteCountResult($modifiedCount);
    }
}
