<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Repository;

use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Collection\UserCollection;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\DuplicateEmailException;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Infrastructure\Repository\MongoDBUserRepository;

use function count;

use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\ODM\MongoDB\DocumentManager;

use function implode;
use function intdiv;
use function mb_strtolower;
use function min;

use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;

use function sprintf;

final class MongoDBUserRepositoryBatchTest extends UnitTestCase
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

    public function testSaveBatchFlushesFullAndFinalChunks(): void
    {
        $users = $this->getUsersForBatchSave(self::BATCH_SIZE + 1);

        $this->expectSaveBatchDocumentManagerCalls($users);

        $this->userRepository->saveBatch($users);
    }

    /**
     * @dataProvider duplicateBatchFlushProvider
     */
    public function testSaveBatchConvertsDuplicateKeyFailureToDuplicateEmailException(
        int $totalUsers,
        int $failingFlushCall,
        int $duplicateUserIndex
    ): void {
        $users = $this->getUsersForBatchSave($totalUsers);
        $duplicateEmail = $users->users[$duplicateUserIndex]->getEmail();
        $error = $this->createDuplicateKeyError($duplicateEmail);

        $this->expectPersistUntilFlushFailure($totalUsers, $failingFlushCall);
        $this->expectFlushFailure($failingFlushCall, $error);
        $this->expectClearCalls($failingFlushCall);
        $this->expectDuplicateEmailException($duplicateEmail);

        $this->userRepository->saveBatch($users);
    }

    public function testSaveBatchRethrowsDuplicateKeyFailureForDifferentEmail(): void
    {
        $totalUsers = self::BATCH_SIZE + 1;
        $failingFlushCall = 1;
        $users = $this->getUsersForBatchSave($totalUsers);
        $error = $this->createDuplicateKeyError($this->faker->unique()->email());

        $this->expectPersistUntilFlushFailure($totalUsers, $failingFlushCall);
        $this->expectFlushFailure($failingFlushCall, $error);
        $this->expectClearCalls($failingFlushCall);
        $this->expectExceptionObject($error);

        $this->userRepository->saveBatch($users);
    }

    public function testDeleteBatchFlushesFullAndFinalChunks(): void
    {
        $totalUsers = self::BATCH_SIZE + 1;
        $users = $this->getUsersForBatchSave($totalUsers);

        $this->expectDeleteBatchDocumentManagerCalls($totalUsers);

        $this->userRepository->deleteBatch($users);
    }

    public function testDeleteBatchFlushesFinalEmptyChunkForExactBatchSize(): void
    {
        $users = $this->getUsersForBatchSave(self::BATCH_SIZE);

        $this->expectDeleteBatchDocumentManagerCalls(self::BATCH_SIZE);

        $this->userRepository->deleteBatch($users);
    }

    /**
     * @return iterable<string, list{int, int, int}>
     */
    public static function duplicateBatchFlushProvider(): iterable
    {
        yield 'chunk flush' => [
            self::BATCH_SIZE + 1,
            1,
            self::BATCH_SIZE - 1,
        ];

        yield 'final flush' => [
            self::BATCH_SIZE + 1,
            2,
            self::BATCH_SIZE,
        ];
    }

    private function getUsersForBatchSave(int $totalUsers): UserCollection
    {
        $users = [];
        for ($index = 0; $index < $totalUsers; ++$index) {
            $users[] = $this->userFactory->create(
                $this->faker->unique()->email(),
                $this->faker->name(),
                $this->faker->password(),
                $this->transformer->transformFromString($this->faker->uuid())
            );
        }

        return new UserCollection($users);
    }

    private function expectSaveBatchDocumentManagerCalls(UserCollection $users): void
    {
        $totalUsers = count($users);
        $expectedFlushCalls = $this->expectedChunkedFlushCalls($totalUsers);

        $this->documentManager
            ->expects($this->exactly($totalUsers))
            ->method('persist')
            ->with($this->isInstanceOf(User::class));

        $this->documentManager
            ->expects($this->exactly($expectedFlushCalls))
            ->method('flush');

        $this->documentManager
            ->expects($this->exactly($expectedFlushCalls))
            ->method('clear');
    }

    private function expectDeleteBatchDocumentManagerCalls(int $totalUsers): void
    {
        $expectedFlushCalls = $this->expectedChunkedFlushCalls($totalUsers);

        $this->documentManager
            ->expects($this->exactly($totalUsers))
            ->method('remove')
            ->with($this->isInstanceOf(User::class));

        $this->documentManager
            ->expects($this->exactly($expectedFlushCalls))
            ->method('flush');

        $this->documentManager
            ->expects($this->exactly($expectedFlushCalls))
            ->method('clear');
    }

    private function createDuplicateKeyError(string $duplicateEmail): RuntimeException
    {
        return new RuntimeException(sprintf(
            implode(' ', [
                'E11000 duplicate key error index:',
                'normalizedEmail_1 dup key:',
                '{ normalizedEmail: "%s" }',
            ]),
            mb_strtolower($duplicateEmail, 'UTF-8')
        ), 11000);
    }

    private function expectPersistUntilFlushFailure(
        int $totalUsers,
        int $failingFlushCall,
    ): void {
        $expectedPersistCalls = min(
            $totalUsers,
            $failingFlushCall * self::BATCH_SIZE
        );

        $this->documentManager
            ->expects($this->exactly($expectedPersistCalls))
            ->method('persist')
            ->with($this->isInstanceOf(User::class));
    }

    private function expectFlushFailure(
        int $failingFlushCall,
        RuntimeException $error,
    ): void {
        $flushCall = 0;

        $this->documentManager
            ->expects($this->exactly($failingFlushCall))
            ->method('flush')
            ->willReturnCallback(
                static function () use (&$flushCall, $failingFlushCall, $error): void {
                    ++$flushCall;

                    if ($flushCall === $failingFlushCall) {
                        throw $error;
                    }
                }
            );
    }

    private function expectClearCalls(int $failingFlushCall): void
    {
        $this->documentManager
            ->expects($this->exactly($failingFlushCall))
            ->method('clear');
    }

    private function expectDuplicateEmailException(string $duplicateEmail): void
    {
        $this->expectException(DuplicateEmailException::class);
        $this->expectExceptionMessage(sprintf(
            'Email "%s" is already registered',
            $duplicateEmail
        ));
    }

    private function expectedChunkedFlushCalls(int $totalUsers): int
    {
        return intdiv($totalUsers, self::BATCH_SIZE) + 1;
    }

    private function getRepository(int $batchSize): MongoDBUserRepository
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
}
