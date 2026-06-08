<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Repository;

use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Exception\DuplicateEmailException;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Infrastructure\Repository\MongoDBUserRepository;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Query\Builder;
use Doctrine\ODM\MongoDB\Query\Query;
use function implode;
use InvalidArgumentException;
use function mb_strtolower;
use function mb_strtoupper;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use function sprintf;
use function trim;

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
            ->willReturn($expectedUser);

        $this->userRepository = $repository;

        $this->assertSame($expectedUser, $this->userRepository->findByEmail($email));
    }

    public function testFindByEmailCaseInsensitiveReturnsUser(): void
    {
        $caseInsensitiveEmail = $this->faker->unique()->email();
        $inputEmail = '  ' . mb_strtoupper($caseInsensitiveEmail, 'UTF-8') . '  ';
        $user = $this->createUserWithEmail($caseInsensitiveEmail);
        [$repository, $queryBuilder, $query] = $this->createQueryBuilderRepository();

        $this->expectFindByEmailsQueryWithEmptyLegacyFallback(
            $repository,
            $queryBuilder,
            $query,
            mb_strtolower($caseInsensitiveEmail, 'UTF-8'),
            [$user]
        );

        $this->assertSame(
            [$user],
            iterator_to_array($repository->findByEmailCaseInsensitive($inputEmail))
        );
    }

    public function testFindByEmailsReturnsUsersForNormalizedUniqueEmails(): void
    {
        $firstEmail = $this->faker->unique()->email();
        $secondEmail = $this->faker->unique()->email();
        $firstUser = $this->createUserWithEmail($firstEmail);
        $secondUser = $this->createUserWithEmail($secondEmail);
        [$repository, $queryBuilder, $query] = $this->createQueryBuilderRepository();

        $this->expectFindByEmailsQueryWithEmptyLegacyFallback(
            $repository,
            $queryBuilder,
            $query,
            [$firstEmail, $secondEmail],
            [$firstUser, $secondUser]
        );

        $users = $repository->findByEmails([$firstEmail, $firstEmail, $secondEmail]);

        $this->assertSame([$firstUser, $secondUser], iterator_to_array($users));
    }

    public function testFindByEmailsQueriesOriginalAndNormalizedEmailCandidates(): void
    {
        $email = $this->faker->unique()->email();
        $inputEmail = mb_strtoupper($email);
        $user = $this->createUserWithEmail($email);
        [$repository, $queryBuilder, $query] = $this->createQueryBuilderRepository();

        $this->expectFindByEmailsQueryWithEmptyLegacyFallback(
            $repository,
            $queryBuilder,
            $query,
            [mb_strtolower($email, 'UTF-8')],
            [$user]
        );

        $users = $repository->findByEmails([$inputEmail]);

        $this->assertSame([$user], iterator_to_array($users));
    }

    public function testFindByEmailsQueriesTrimmedAndNormalizedEmailCandidates(): void
    {
        $email = $this->faker->unique()->email();
        $trimmedEmail = mb_strtoupper($email);
        $inputEmail = '  ' . $trimmedEmail . '  ';
        $user = $this->createUserWithEmail($email);
        [$repository, $queryBuilder, $query] = $this->createQueryBuilderRepository();

        $this->expectFindByEmailsQueryWithEmptyLegacyFallback(
            $repository,
            $queryBuilder,
            $query,
            [mb_strtolower($email, 'UTF-8')],
            [$user]
        );

        $users = $repository->findByEmails([$inputEmail]);

        $this->assertSame([$user], iterator_to_array($users));
    }

    public function testFindByEmailsReturnsEmptyArrayWhenInputIsEmpty(): void
    {
        [$repository] = $this->createQueryBuilderRepository();

        $repository->expects($this->never())
            ->method('createQueryBuilder');

        $this->assertCount(0, $repository->findByEmails([]));
    }

    public function testFindByEmailsSkipsNonUserResults(): void
    {
        $email = $this->faker->unique()->email();
        $user = $this->createUserWithEmail($email);
        [$repository, $queryBuilder, $query] = $this->createQueryBuilderRepository();

        $this->expectFindByEmailsQueryWithEmptyLegacyFallback(
            $repository,
            $queryBuilder,
            $query,
            [$email],
            [$user, new \stdClass()]
        );

        $this->assertSame([$user], iterator_to_array($repository->findByEmails([$email])));
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

        $this->documentManager->expects($this->once())
            ->method('persist')->with($user);
        $this->documentManager->expects($this->once())
            ->method('flush')->willThrowException($error);
        $this->documentManager->expects($this->once())
            ->method('detach')
            ->with($user);

        $this->expectExceptionObject($error);

        $this->userRepository->save($user);
    }

    public function testSaveConvertsDuplicateKeyFailureToDuplicateEmailException(): void
    {
        $email = $this->faker->email();
        $user = $this->createMock(UserInterface::class);
        $error = new RuntimeException(sprintf(
            implode(' ', [
                'E11000 duplicate key error index:',
                'normalizedEmail_1 dup key:',
                '{ normalizedEmail: "%s" }',
            ]),
            mb_strtolower($email, 'UTF-8')
        ), 11000);

        $user->method('getEmail')->willReturn($email);
        $this->documentManager->expects($this->once())->method('persist')->with($user);
        $this->documentManager->expects($this->once())->method('flush')->willThrowException($error);
        $this->documentManager->expects($this->once())->method('detach')->with($user);
        $this->expectException(DuplicateEmailException::class);
        $this->expectExceptionMessage(sprintf('Email "%s" is already registered', $email));

        $this->userRepository->save($user);
    }

    public function testSaveRethrowsDuplicateEmailFailureForDifferentEmail(): void
    {
        $email = $this->faker->unique()->email();
        $otherEmail = $this->faker->unique()->email();
        $user = $this->createMock(UserInterface::class);
        $error = new RuntimeException(sprintf(
            implode(' ', [
                'E11000 duplicate key error index:',
                'normalizedEmail_1 dup key:',
                '{ normalizedEmail: "%s" }',
            ]),
            mb_strtolower($otherEmail, 'UTF-8')
        ), 11000);

        $user->method('getEmail')->willReturn($email);
        $this->documentManager->expects($this->once())->method('persist')->with($user);
        $this->documentManager->expects($this->once())->method('flush')->willThrowException($error);
        $this->documentManager->expects($this->once())->method('detach')->with($user);
        $this->expectExceptionObject($error);

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

    public function testDeleteAll(): void
    {
        $queryBuilder = $this->createMock(Builder::class);
        $query = $this->createMock(Query::class);

        $repository = $this->getMockBuilder(MongoDBUserRepository::class)
            ->setConstructorArgs([
                $this->documentManager,
                $this->registry,
                self::BATCH_SIZE,
            ])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $repository->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())->method('remove')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('getQuery')->willReturn($query);
        $query->expects($this->once())->method('execute');

        $repository->deleteAll();
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

    /**
     * @return array{MongoDBUserRepository, Builder, Query}
     */
    private function createQueryBuilderRepository(): array
    {
        $queryBuilder = $this->createMock(Builder::class);
        $query = $this->createMock(Query::class);
        $repository = $this->getMockBuilder(MongoDBUserRepository::class)
            ->setConstructorArgs([
                $this->documentManager,
                $this->registry,
                self::BATCH_SIZE,
            ])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        return [$repository, $queryBuilder, $query];
    }

    /**
     * @param list<string>|string $emails
     * @param list<object> $queryResult
     */
    private function expectFindByEmailsQuery(
        MongoDBUserRepository $repository,
        Builder $queryBuilder,
        Query $query,
        array|string $emails,
        array $queryResult
    ): void {
        $repository->expects($this->once())->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $this->expectNormalizedEmailQuery($queryBuilder, $query, $emails, $queryResult);
    }

    /**
     * @param list<string>|string $emails
     * @param list<object> $queryResult
     */
    private function expectFindByEmailsQueryWithEmptyLegacyFallback(
        MongoDBUserRepository $repository,
        Builder $queryBuilder,
        Query $query,
        array|string $emails,
        array $queryResult
    ): void {
        $legacyQueryBuilder = $this->createMock(Builder::class);
        $legacyQuery = $this->createMock(Query::class);

        $repository->expects($this->exactly(2))->method('createQueryBuilder')
            ->willReturnOnConsecutiveCalls($queryBuilder, $legacyQueryBuilder);

        $this->expectNormalizedEmailQuery($queryBuilder, $query, $emails, $queryResult);
        $this->expectLegacyFallbackQuery($legacyQueryBuilder, $legacyQuery, []);
    }

    /**
     * @param list<string>|string $emails
     * @param list<object> $queryResult
     */
    private function expectNormalizedEmailQuery(
        Builder $queryBuilder,
        Query $query,
        array|string $emails,
        array $queryResult
    ): void {
        $this->expectNormalizedEmailConstraint($queryBuilder, $emails);
        $this->expectQueryExecution($queryBuilder, $query, $queryResult);
    }

    /**
     * @param list<string>|string $emails
     */
    private function expectNormalizedEmailConstraint(
        Builder $queryBuilder,
        array|string $emails
    ): void {
        $queryBuilder->expects($this->once())->method('field')->with('normalizedEmail')
            ->willReturnSelf();

        if (is_string($emails)) {
            $queryBuilder->expects($this->once())
                ->method('equals')
                ->with($emails)
                ->willReturnSelf();

            return;
        }

        $queryBuilder->expects($this->once())
            ->method('in')
            ->with($emails)
            ->willReturnSelf();
    }

    /**
     * @param list<object> $queryResult
     */
    private function expectLegacyFallbackQuery(
        Builder $queryBuilder,
        Query $query,
        array $queryResult
    ): void {
        $queryBuilder->expects($this->once())
            ->method('addAnd')
            ->willReturnSelf();

        $this->expectQueryExecution($queryBuilder, $query, $queryResult);
    }

    /**
     * @param list<object> $queryResult
     */
    private function expectQueryExecution(
        Builder $queryBuilder,
        Query $query,
        array $queryResult
    ): void {
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects($this->once())
            ->method('execute')
            ->willReturn($queryResult);
    }

    private function createUserWithEmail(string $email): User
    {
        return $this->userFactory->create(
            $email,
            $this->faker->name(),
            $this->faker->password(),
            $this->transformer->transformFromString($this->faker->uuid())
        );
    }
}
