<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Repository;

use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Repository\MariaDBUserRepository;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Logging\Middleware;
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
    private Connection $connection;

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
        $this->transformer = new UuidTransformer(new UuidFactory());
        $this->connection = $this->createMock(Connection::class);
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

    public function testFindById(): void
    {
        $id = $this->faker->uuid();
        $expectedUser = $this->createMock(UserInterface::class);

        // Create a mock repository that overrides just the find method
        $repository = $this->getMockBuilder(MariaDBUserRepository::class)
            ->setConstructorArgs([
                $this->entityManager,
                $this->registry,
                self::BATCH_SIZE,
            ])
            ->onlyMethods(['find'])
            ->getMock();

        $repository->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($expectedUser);

        $result = $repository->findById($id);

        $this->assertSame($expectedUser, $result);
    }

    public function testFindByIdReturnsNull(): void
    {
        $id = $this->faker->uuid();

        // Create a mock repository that overrides just the find method
        $repository = $this->getMockBuilder(MariaDBUserRepository::class)
            ->setConstructorArgs([
                $this->entityManager,
                $this->registry,
                self::BATCH_SIZE,
            ])
            ->onlyMethods(['find'])
            ->getMock();

        $repository->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn(null);

        $result = $repository->findById($id);

        $this->assertNull($result);
    }

    public function testSaveBatchSetsMiddleware(): void
    {
        $users = $this->createUsers(self::BATCH_SIZE);

        $this->setUpMiddlewareExpectations();
        $this->setUpEntityManagerExpectations();

        $this->userRepository->saveBatch($users);
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

        $metadataMock = $this->createMock(ClassMetadata::class);
        $metadataMock->name = User::class;
        $this->entityManager->expects($this->once())
            ->method('getClassMetadata')
            ->willReturn($metadataMock);

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
        int $batchSize = self::BATCH_SIZE,
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
        $this->entityManager->expects($this->once())
            ->method('getConnection')
            ->willReturn($this->connection);
        $this->connection->expects($this->once())
            ->method('getConfiguration')
            ->willReturn($this->createMock(Configuration::class));
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

    private function setUpMiddlewareExpectations(): void
    {
        $configurationMock = $this->createMock(Configuration::class);
        $configurationMock->expects($this->once())
            ->method('setMiddlewares')
            ->with($this->callback(static function ($middlewares) {
                return isset(
                    $middlewares[0]
                ) && $middlewares[0] instanceof Middleware;
            }));

        $this->connection->expects($this->once())
            ->method('getConfiguration')
            ->willReturn($configurationMock);

        $this->entityManager->expects($this->atLeastOnce())
            ->method('getConnection')
            ->willReturn($this->connection);
    }

    private function setUpEntityManagerExpectations(): void
    {
        $this->entityManager->expects($this->atLeastOnce())
            ->method('persist');
        $this->entityManager->expects($this->atLeastOnce())
            ->method('flush');
        $this->entityManager->expects($this->atLeastOnce())
            ->method('clear');
    }

    /**
     * @return array<User> $users
     */
    private function createUsers(int $count): array
    {
        $users = [];
        for ($i = 0; $i < $count; $i++) {
            $users[] = $this->userFactory->create(
                $this->faker->email(),
                $this->faker->name(),
                $this->faker->password(),
                $this->transformer->transformFromString($this->faker->uuid())
            );
        }

        return $users;
    }
}
