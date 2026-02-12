<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Repository;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\AuthRefreshToken;
use App\User\Infrastructure\Repository\MongoDBAuthRefreshTokenRepository;
use DateTimeImmutable;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\ODM\MongoDB\DocumentManager;
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

    private function createAuthRefreshToken(): AuthRefreshToken
    {
        return new AuthRefreshToken(
            $this->faker->uuid(),
            $this->faker->uuid(),
            $this->faker->sha256(),
            new DateTimeImmutable('+1 month')
        );
    }
}
