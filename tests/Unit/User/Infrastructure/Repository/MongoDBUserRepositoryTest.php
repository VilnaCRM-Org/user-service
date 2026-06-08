<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Repository;

use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Exception\DuplicateEmailException;
use App\User\Infrastructure\Repository\MongoDBUserRepository;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Query\Builder;
use Doctrine\ODM\MongoDB\Query\Query;
use function implode;
use InvalidArgumentException;
use function mb_strtolower;
use function mb_strtoupper;
use RuntimeException;
use function sprintf;
use function trim;

final class MongoDBUserRepositoryTest extends MongoDBUserRepositoryTestCase
{
    public function testConstructorThrowsExceptionForInvalidBatchSizes(): void
    {
        foreach ([0, -1] as $batchSize) {
            $registry = $this->createMock(ManagerRegistry::class);
            $documentManager = $this->createMock(DocumentManager::class);

            try {
                new MongoDBUserRepository(
                    $documentManager,
                    $registry,
                    $batchSize
                );
            } catch (InvalidArgumentException $exception) {
                $this->assertSame(
                    'Batch size must be greater than zero.',
                    $exception->getMessage()
                );
                continue;
            }

            $this->fail('Expected invalid batch size exception.');
        }
    }

    public function testFindByEmailReturnsUser(): void
    {
        $email = $this->faker->email();
        $expectedUser = $this->createUserWithEmail($email);

        $repository = $this->createRepositoryMock(['findOneBy']);

        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => $email])
            ->willReturn($expectedUser);

        $this->userRepository = $repository;

        $this->assertSame($expectedUser, $this->userRepository->findByEmail($email));
    }

    public function testFindById(): void
    {
        $id = $this->faker->uuid();
        $expectedUser = $this->createMock(UserInterface::class);

        $repository = $this->createRepositoryMock(['find']);

        $repository->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($expectedUser);

        $user = $repository->findById($id);

        $this->assertSame($expectedUser, $user);
    }

    public function testSaveUser(): void
    {
        $email = '  ' . mb_strtoupper($this->faker->unique()->email(), 'UTF-8') . '  ';
        $user = $this->createUserWithEmail($email);

        $this->documentManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(
                static fn (User $persistedUser): bool => $persistedUser === $user
                    && $persistedUser->getNormalizedEmail() === mb_strtolower(trim($email), 'UTF-8')
            ));

        $this->documentManager
            ->expects($this->once())
            ->method('flush');

        $this->userRepository->save($user);
    }

    public function testSaveDetachesFailedUserWhenFlushFails(): void
    {
        $user = $this->createMock(UserInterface::class);
        $error = new RuntimeException(
            'E11000 duplicate key error index: _id_',
            11000
        );

        $this->expectPersistFlushFailureAndDetach($user, $error);

        $this->expectExceptionObject($error);

        $this->userRepository->save($user);
    }

    public function testSaveConvertsDuplicateKeyFailureToDuplicateEmailException(): void
    {
        $email = $this->faker->email();
        $user = $this->createMock(UserInterface::class);
        $error = $this->duplicateNormalizedEmailException($email);

        $user->method('getEmail')->willReturn($email);
        $this->expectPersistFlushFailureAndDetach($user, $error);
        $this->expectException(DuplicateEmailException::class);
        $this->expectExceptionMessage(sprintf('Email "%s" is already registered', $email));

        $this->userRepository->save($user);
    }

    public function testSaveRethrowsDuplicateEmailFailureForDifferentEmail(): void
    {
        $email = $this->faker->unique()->email();
        $otherEmail = $this->faker->unique()->email();
        $user = $this->createMock(UserInterface::class);
        $error = $this->duplicateNormalizedEmailException($otherEmail);

        $user->method('getEmail')->willReturn($email);
        $this->expectPersistFlushFailureAndDetach($user, $error);
        $this->expectExceptionObject($error);

        $this->userRepository->save($user);
    }

    public function testDeleteUser(): void
    {
        $user = $this->createMock(UserInterface::class);

        $this->expectDocumentRemove($user);
        $this->expectDocumentFlush();

        $this->userRepository->delete($user);
    }

    public function testDeleteAll(): void
    {
        $queryBuilder = $this->createMock(Builder::class);
        $query = $this->createMock(Query::class);

        $repository = $this->createRepositoryMock(['createQueryBuilder']);

        $repository->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())->method('remove')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('getQuery')->willReturn($query);
        $query->expects($this->once())->method('execute');

        $repository->deleteAll();
    }

    private function expectPersistFlushFailureAndDetach(
        object $document,
        RuntimeException $error
    ): void {
        $this->documentManager->expects($this->once())->method('persist')->with($document);
        $this->documentManager->expects($this->once())->method('flush')->willThrowException($error);
        $this->documentManager->expects($this->once())->method('detach')->with($document);
    }

    private function expectDocumentRemove(object $document): void
    {
        $this->documentManager
            ->expects($this->once())
            ->method('remove')
            ->with($document);
    }

    private function expectDocumentFlush(): void
    {
        $this->documentManager
            ->expects($this->once())
            ->method('flush');
    }

    private function duplicateNormalizedEmailException(string $email): RuntimeException
    {
        return new RuntimeException(sprintf(
            implode(' ', [
                'E11000 duplicate key error index:',
                'normalizedEmail_1 dup key:',
                '{ normalizedEmail: "%s" }',
            ]),
            mb_strtolower($email, 'UTF-8')
        ), 11000);
    }
}
