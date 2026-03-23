<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Repository;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\AuthSession;
use App\User\Infrastructure\Repository\MongoDBAuthSessionRepository;
use DateTimeImmutable;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\ODM\MongoDB\DocumentManager;
use PHPUnit\Framework\MockObject\MockObject;

final class MongoDBAuthSessionRepositoryTest extends UnitTestCase
{
    private DocumentManager|MockObject $documentManager;
    private ManagerRegistry|MockObject $registry;
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

        $this->repository = new MongoDBAuthSessionRepository(
            $this->documentManager,
            $this->registry
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
            ->setConstructorArgs([$this->documentManager, $this->registry])
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
        $sessions = [
            $this->createAuthSession(),
            $this->createAuthSession(),
        ];

        $repositoryClass = MongoDBAuthSessionRepository::class;
        $repository = $this->getMockBuilder($repositoryClass)
            ->setConstructorArgs(
                [$this->documentManager, $this->registry]
            )
            ->onlyMethods(['findBy'])
            ->getMock();

        $repository->expects($this->once())
            ->method('findBy')
            ->with(['userId' => $userId])
            ->willReturn($sessions);

        $result = $repository->findByUserId($userId);
        $this->assertSame($sessions, $result);
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
}
