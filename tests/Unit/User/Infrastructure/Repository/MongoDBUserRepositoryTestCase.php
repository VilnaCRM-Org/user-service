<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Repository;

use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Infrastructure\Repository\MongoDBUserRepository;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\ODM\MongoDB\DocumentManager;
use PHPUnit\Framework\MockObject\MockObject;

abstract class MongoDBUserRepositoryTestCase extends UnitTestCase
{
    protected const BATCH_SIZE = 3;

    protected DocumentManager|MockObject $documentManager;
    protected ManagerRegistry|MockObject $registry;
    protected MongoDBUserRepository $userRepository;
    protected UserFactoryInterface $userFactory;
    protected UuidTransformer $transformer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->documentManager =
            $this->createMock(DocumentManager::class);
        $this->registry =
            $this->createMock(ManagerRegistry::class);
        $this->userRepository =
            $this->getRepository(self::BATCH_SIZE);
        $this->userFactory = new UserFactory();
        $this->transformer = new UuidTransformer(new UuidFactory());
    }

    protected function getRepository(int $batchSize): MongoDBUserRepository
    {
        $this->registry
            ->expects($this->atLeastOnce())
            ->method('getManagerForClass')
            ->with(User::class)
            ->willReturn($this->documentManager);

        return new MongoDBUserRepository(
            $this->documentManager,
            $this->registry,
            $batchSize
        );
    }

    /**
     * @param non-empty-list<string> $methods
     */
    protected function createRepositoryMock(array $methods): MongoDBUserRepository
    {
        return $this->getMockBuilder(MongoDBUserRepository::class)
            ->setConstructorArgs([
                $this->documentManager,
                $this->registry,
                self::BATCH_SIZE,
            ])
            ->onlyMethods($methods)
            ->getMock();
    }

    protected function createUserWithEmail(string $email): User
    {
        return $this->userFactory->create(
            $email,
            $this->faker->name(),
            $this->faker->password(),
            $this->transformer->transformFromString($this->faker->uuid())
        );
    }
}
