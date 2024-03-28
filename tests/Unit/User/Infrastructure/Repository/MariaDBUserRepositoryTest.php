<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Repository;

use App\Shared\Application\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Infrastructure\Repository\MariaDBUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Persisters\Entity\EntityPersister;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;

class MariaDBUserRepositoryTest extends UnitTestCase
{
    private EntityManagerInterface|MockObject $entityManager;
    private ManagerRegistry|MockObject $registry;
    private MariaDBUserRepository $userRepository;
    private UserFactoryInterface $userFactory;
    private UuidTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->userRepository = new MariaDBUserRepository($this->entityManager, $this->registry);
        $this->userFactory = new UserFactory();
        $this->transformer = new UuidTransformer();
    }

    public function testFindByEmailReturnsUser(): void
    {
        $email = $this->faker->email();
        $initials = $this->faker->name();
        $password = $this->faker->password();

        $expectedUser = $this->userFactory->create(
            $email,
            $initials,
            $password,
            $this->transformer->transformFromString($this->faker->uuid())
        );

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $persister = $this->createMock(EntityPersister::class);

        $this->entityManager->expects($this->once())
            ->method('getClassMetadata')
            ->willReturn($this->createMock(ClassMetadata::class));
        $this->entityManager->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        $unitOfWork->expects($this->once())
            ->method('getEntityPersister')
            ->willReturn($persister);

        $persister->expects($this->once())
            ->method('load')
            ->with(['email' => $email], null, null, [], null, 1, null)
            ->willReturn($expectedUser);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);

        $user = $this->userRepository->findByEmail($email);

        $this->assertSame($expectedUser, $user);
    }

    public function testSave(): void
    {
        $email = $this->faker->email();
        $initials = $this->faker->name();
        $password = $this->faker->password();

        $user = $this->userFactory->create(
            $email,
            $initials,
            $password,
            $this->transformer->transformFromString($this->faker->uuid())
        );

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($user);
        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->userRepository->save($user);

        $this->addToAssertionCount(1);
    }

    public function testDelete(): void
    {
        $email = $this->faker->email();
        $initials = $this->faker->name();
        $password = $this->faker->password();

        $user = $this->userFactory->create(
            $email,
            $initials,
            $password,
            $this->transformer->transformFromString($this->faker->uuid())
        );

        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($user);
        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->userRepository->delete($user);

        $this->addToAssertionCount(1);
    }
}
