<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Repository;

use App\Shared\Application\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Repository\MariaDBUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Persisters\Entity\EntityPersister;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;

final class MariaDBUserRepositoryTest extends UnitTestCase
{
    private const BATCH_SIZE = 3;
    private EntityManagerInterface|MockObject $entityManager;
    private ManagerRegistry|MockObject $registry;
    private MariaDBUserRepository $userRepository;
    private UserFactoryInterface $userFactory;
    private UuidTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager =
            $this->createMock(EntityManagerInterface::class);
        $this->registry =
            $this->createMock(ManagerRegistry::class);
        $this->userRepository =
            $this->getRepository($this->faker->numberBetween(1, 20));
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

        $this->testFindByEmailReturnsUserSetExpectations($expectedUser, $email);

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
    }

    public function testSaveBatch(): void
    {
        $users = [];
        for ($i = 0; $i < self::BATCH_SIZE; $i++) {
            $email = $this->faker->email();
            $initials = $this->faker->name();
            $password = $this->faker->password();

            $users[] = $this->userFactory->create(
                $email,
                $initials,
                $password,
                $this->transformer->transformFromString($this->faker->uuid())
            );
        }

        $this->testSaveBatchSetEntityManagerExpectations($users);

        $this->entityManager->expects($this->atLeast(1))
            ->method('flush');
        $this->entityManager->expects($this->atLeast(1))
            ->method('clear');

        $this->userRepository->saveBatch($users);
    }

    public function testSaveBatchExactBatchSize(): void
    {
        $repository = $this->getRepository();
        $users = [];
        for ($i = 0; $i < self::BATCH_SIZE; $i++) {
            $email = $this->faker->email();
            $initials = $this->faker->name();
            $password = $this->faker->password();

            $users[] = $this->userFactory->create(
                $email,
                $initials,
                $password,
                $this->transformer->transformFromString($this->faker->uuid())
            );
        }

        $this->testSaveBatchSetEntityManagerExpectations($users);

        $this->entityManager->expects($this->exactly(2))
            ->method('flush');
        $this->entityManager->expects($this->exactly(2))
            ->method('clear');

        $repository->saveBatch($users);
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
    }

    private function testFindByEmailReturnsUserSetExpectations(
        UserInterface $expectedUser,
        string $email
    ): void {
        $unitOfWork = $this->createMock(UnitOfWork::class);
        $persister = $this->createMock(EntityPersister::class);

        $this->entityManager->expects($this->once())
            ->method('getClassMetadata')
            ->willReturn(
                $this->createMock(ClassMetadata::class)
            );
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
    }

    private function getRepository(
        int $batchSize = self::BATCH_SIZE
    ): UserRepositoryInterface {
        return new MariaDBUserRepository(
            $this->entityManager,
            $this->registry,
            $batchSize
        );
    }

    /**
     * @param array<UserInterface> $users
     */
    private function testSaveBatchSetEntityManagerExpectations(
        array $users
    ): void {
        $this->entityManager->expects($this->exactly(self::BATCH_SIZE))
            ->method('persist')
            ->withConsecutive(
                ...array_map(
                    static function ($user) {
                        return [$user];
                    },
                    $users
                )
            );
    }
}
