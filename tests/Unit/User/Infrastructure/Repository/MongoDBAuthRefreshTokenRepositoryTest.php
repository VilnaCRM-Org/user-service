<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Repository;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\AuthRefreshToken;
use App\User\Infrastructure\Repository\MongoDBAuthRefreshTokenRepository;
use DateTimeImmutable;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Query\Builder;
use Doctrine\ODM\MongoDB\Query\Query;
use PHPUnit\Framework\MockObject\MockObject;

final class MongoDBAuthRefreshTokenRepositoryTest extends UnitTestCase
{
    private DocumentManager|MockObject $documentManager;
    private ManagerRegistry|MockObject $registry;
    private MongoDBAuthRefreshTokenRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->documentManager = $this->createMock(DocumentManager::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->registry
            ->expects($this->atLeastOnce())
            ->method('getManagerForClass')
            ->with(AuthRefreshToken::class)
            ->willReturn($this->documentManager);

        $this->repository = new MongoDBAuthRefreshTokenRepository(
            $this->documentManager,
            $this->registry
        );
    }

    public function testSave(): void
    {
        $token = $this->createAuthRefreshToken();

        $this->documentManager->expects($this->once())
            ->method('persist')
            ->with($token);
        $this->documentManager->expects($this->once())
            ->method('flush');

        $this->repository->save($token);
    }

    public function testFindById(): void
    {
        $id = $this->faker->uuid();
        $expectedToken = $this->createAuthRefreshToken();
        $repositoryClass = MongoDBAuthRefreshTokenRepository::class;
        $repository = $this->getMockBuilder($repositoryClass)
            ->setConstructorArgs([$this->documentManager, $this->registry])
            ->onlyMethods(['find'])
            ->getMock();

        $repository->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($expectedToken);

        $this->assertSame($expectedToken, $repository->findById($id));
    }

    public function testFindByTokenHash(): void
    {
        $tokenHash = $this->faker->sha256();
        $expectedToken = $this->createAuthRefreshToken();
        $repositoryClass = MongoDBAuthRefreshTokenRepository::class;
        $repository = $this->getMockBuilder($repositoryClass)
            ->setConstructorArgs([$this->documentManager, $this->registry])
            ->onlyMethods(['findOneBy'])
            ->getMock();

        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(['tokenHash' => $tokenHash])
            ->willReturn($expectedToken);

        $this->assertSame($expectedToken, $repository->findByTokenHash($tokenHash));
    }

    public function testFindBySessionId(): void
    {
        $sessionId = $this->faker->uuid();
        $expectedTokens = [
            $this->createAuthRefreshToken(),
            $this->createAuthRefreshToken(),
        ];

        $repositoryClass = MongoDBAuthRefreshTokenRepository::class;
        $repository = $this->getMockBuilder($repositoryClass)
            ->setConstructorArgs([$this->documentManager, $this->registry])
            ->onlyMethods(['findBy'])
            ->getMock();

        $repository->expects($this->once())
            ->method('findBy')
            ->with(['sessionId' => $sessionId])
            ->willReturn($expectedTokens);

        $this->assertSame($expectedTokens, $repository->findBySessionId($sessionId));
    }

    public function testDelete(): void
    {
        $token = $this->createAuthRefreshToken();

        $this->documentManager->expects($this->once())
            ->method('remove')
            ->with($token);
        $this->documentManager->expects($this->once())
            ->method('flush');

        $this->repository->delete($token);
    }

    public function testRevokeBySessionId(): void
    {
        $sessionId = $this->faker->uuid();
        $activeToken = $this->createAuthRefreshToken();
        $alreadyRevokedToken = $this->createAuthRefreshToken();
        $alreadyRevokedToken->revoke();

        $repositoryClass = MongoDBAuthRefreshTokenRepository::class;
        $repository = $this->getMockBuilder($repositoryClass)
            ->setConstructorArgs([$this->documentManager, $this->registry])
            ->onlyMethods(['findBySessionId'])
            ->getMock();

        $repository->expects($this->once())
            ->method('findBySessionId')
            ->with($sessionId)
            ->willReturn([$activeToken, $alreadyRevokedToken]);

        $this->documentManager->expects($this->once())
            ->method('persist')
            ->with($activeToken);

        $this->documentManager->expects($this->once())
            ->method('flush');

        $repository->revokeBySessionId($sessionId);

        $this->assertNotNull($activeToken->getRevokedAt());
    }

    public function testMarkAsRotatedIfActiveReturnsTrueWhenUpdateReturnsPositiveInt(): void
    {
        $repository = $this->createRepositoryWithUpdateResult(1);

        $this->assertTrue(
            $repository->markAsRotatedIfActive(
                $this->faker->sha256(),
                new DateTimeImmutable()
            )
        );
    }

    public function testMarkAsRotatedIfActiveReturnsFalseWhenUpdateReturnsZeroInt(): void
    {
        $repository = $this->createRepositoryWithUpdateResult(0);

        $this->assertFalse(
            $repository->markAsRotatedIfActive(
                $this->faker->sha256(),
                new DateTimeImmutable()
            )
        );
    }

    public function testMarkGraceUsedIfEligibleReturnsTrueWhenModifiedCountIsPositiveInt(): void
    {
        $repository = $this->createRepositoryWithUpdateResult(new class() {
            public function getModifiedCount(): int
            {
                return 1;
            }
        });

        $this->assertTrue(
            $repository->markGraceUsedIfEligible(
                $this->faker->sha256(),
                new DateTimeImmutable('-1 minute'),
                new DateTimeImmutable()
            )
        );
    }

    public function testMarkGraceUsedIfEligibleReturnsFalseWhenModifiedCountIsNotInt(): void
    {
        $repository = $this->createRepositoryWithUpdateResult(new class() {
            public function getModifiedCount(): string
            {
                return '1';
            }
        });

        $this->assertFalse(
            $repository->markGraceUsedIfEligible(
                $this->faker->sha256(),
                new DateTimeImmutable('-1 minute'),
                new DateTimeImmutable()
            )
        );
    }

    public function testMarkAsRotatedIfActiveReturnsFalseWhenResultHasNoModifiedCountMethod(): void
    {
        $repository = $this->createRepositoryWithUpdateResult(new \stdClass());

        $this->assertFalse(
            $repository->markAsRotatedIfActive(
                $this->faker->sha256(),
                new DateTimeImmutable()
            )
        );
    }

    private function createAuthRefreshToken(): AuthRefreshToken
    {
        return new AuthRefreshToken(
            $this->faker->uuid(),
            $this->faker->uuid(),
            $this->faker->sha256(),
            new DateTimeImmutable('+1 month')
        );
    }

    private function createRepositoryWithUpdateResult(
        mixed $updateResult
    ): MongoDBAuthRefreshTokenRepository {
        $repositoryClass = MongoDBAuthRefreshTokenRepository::class;
        $repository = $this->getMockBuilder($repositoryClass)
            ->setConstructorArgs([$this->documentManager, $this->registry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $queryBuilder = $this->createMock(Builder::class);
        $query = $this->createMock(Query::class);

        $queryBuilder->method('updateOne')->willReturnSelf();
        $queryBuilder->method('field')->willReturnSelf();
        $queryBuilder->method('equals')->willReturnSelf();
        $queryBuilder->method('gt')->willReturnSelf();
        $queryBuilder->method('gte')->willReturnSelf();
        $queryBuilder->method('set')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);

        $query->method('execute')->willReturn($updateResult);

        $repository->method('createQueryBuilder')->willReturn($queryBuilder);

        return $repository;
    }
}
