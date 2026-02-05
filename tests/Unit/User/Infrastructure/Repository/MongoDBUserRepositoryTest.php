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
use App\User\Infrastructure\Repository\MongoDBUserRepository;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\ODM\MongoDB\DocumentManager;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;

final class MongoDBUserRepositoryTest extends UnitTestCase
{
    private const BATCH_SIZE = 3;
    private DocumentManager|MockObject $documentManager;
    private ManagerRegistry|MockObject $registry;
    private MongoDBUserRepository $userRepository;
    private UserFactoryInterface $userFactory;
    private UuidTransformer $transformer;

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

    public function testConstructorThrowsExceptionForZeroBatchSize(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Batch size must be greater than zero.');

        $registry = $this->createMock(ManagerRegistry::class);
        $documentManager = $this->createMock(DocumentManager::class);

        new MongoDBUserRepository(
            $documentManager,
            $registry,
            0
        );
    }

    public function testConstructorThrowsExceptionForNegativeBatchSize(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Batch size must be greater than zero.');

        $registry = $this->createMock(ManagerRegistry::class);
        $documentManager = $this->createMock(DocumentManager::class);

        new MongoDBUserRepository(
            $documentManager,
            $registry,
            -1
        );
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

        $repository = $this->getMockBuilder(MongoDBUserRepository::class)
            ->setConstructorArgs([
                $this->documentManager,
                $this->registry,
                self::BATCH_SIZE,
            ])
            ->onlyMethods(['find'])
            ->getMock();

        $repository->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($expectedUser);

        $user = $repository->findById($id);

        $this->assertSame($expectedUser, $user);
    }

    public function testSaveUser(): void
    {
        $user = $this->createMock(UserInterface::class);

        $this->documentManager
            ->expects($this->once())
            ->method('persist')
            ->with($user);

        $this->documentManager
            ->expects($this->once())
            ->method('flush');

        $this->userRepository->save($user);
    }

    public function testDeleteUser(): void
    {
        $user = $this->createMock(UserInterface::class);

        $this->documentManager
            ->expects($this->once())
            ->method('remove')
            ->with($user);

        $this->documentManager
            ->expects($this->once())
            ->method('flush');

        $this->userRepository->delete($user);
    }

    public function testSaveBatch(): void
    {
        $users = $this->getUsersForBatchSave(self::BATCH_SIZE + 1);

        $this->testSaveBatchSetDocumentManagerExpectations($users);

        $this->userRepository->saveBatch($users);
    }

    public function testSaveBatchWithPartialBatch(): void
    {
        $batchSize = self::BATCH_SIZE;
        $totalUsers = $batchSize + 1;
        $users = $this->getUsersForBatchSave($totalUsers);

        $this->testSaveBatchSetDocumentManagerExpectations($users);

        $this->userRepository->saveBatch($users);
    }

    /**
     * @return array<User>
     */
    private function getUsersForBatchSave(int $count): array
    {
        $users = [];
        for ($i = 0; $i < $count; ++$i) {
            $users[] = $this->userFactory->create(
                $this->faker->unique()->email(),
                $this->faker->name(),
                $this->faker->password(),
                $this->transformer->transformFromString($this->faker->uuid())
            );
        }

        return $users;
    }

    /**
     * @param array<User> $users
     */
    private function testSaveBatchSetDocumentManagerExpectations(array $users): void
    {
        $totalUsers = count($users);
        $fullBatches = (int) floor($totalUsers / self::BATCH_SIZE);

        $expectedFlushCalls = $fullBatches + 1;
        $expectedClearCalls = $expectedFlushCalls;

        $this->documentManager
            ->expects($this->exactly($totalUsers))
            ->method('persist')
            ->with($this->isInstanceOf(User::class));

        $this->documentManager
            ->expects($this->exactly($expectedFlushCalls))
            ->method('flush');

        $this->documentManager
            ->expects($this->exactly($expectedClearCalls))
            ->method('clear');
    }

    private function getRepository(int $batchSize): UserRepositoryInterface
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

    private function testFindByEmailReturnsUserSetExpectations(
        UserInterface $user,
        string $email
    ): void {
        $repository = $this->getMockBuilder(MongoDBUserRepository::class)
            ->setConstructorArgs([
                $this->documentManager,
                $this->registry,
                self::BATCH_SIZE,
            ])
            ->onlyMethods(['findOneBy'])
            ->getMock();

        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => $email])
            ->willReturn($user);

        $this->userRepository = $repository;
    }
}
