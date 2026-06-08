<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Repository;

use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\UserFactory;
use App\User\Infrastructure\Repository\MongoDBUserRepository;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Query\Builder;
use Doctrine\ODM\MongoDB\Query\Query;
use function mb_strtolower;
use function mb_strtoupper;
use PHPUnit\Framework\MockObject\MockObject;
use function preg_quote;

/**
 * @psalm-type LegacyFallbackFixture = array{
 *     inputEmail: string,
 *     normalizedEmail: string,
 *     user: User,
 *     repository: MongoDBUserRepository,
 *     currentQueryBuilder: Builder,
 *     currentQuery: Query,
 *     legacyQueryBuilder: Builder,
 *     legacyQuery: Query
 * }
 */
final class MongoDBUserRepositoryLegacyFallbackTest extends UnitTestCase
{
    private const BATCH_SIZE = 3;

    private DocumentManager|MockObject $documentManager;
    private ManagerRegistry|MockObject $registry;
    private UserFactory $userFactory;
    private UuidTransformer $transformer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->documentManager = $this->createMock(DocumentManager::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->userFactory = new UserFactory();
        $this->transformer = new UuidTransformer(new UuidFactory());
    }

    public function testFindByEmailCaseInsensitiveFallsBackToLegacyDocuments(): void
    {
        $fixture = $this->createLegacyFallbackFixture();

        $this->expectLegacyFallbackQueries($fixture, $fixture['normalizedEmail']);

        $this->assertSame(
            [$fixture['user']],
            iterator_to_array(
                $fixture['repository']->findByEmailCaseInsensitive($fixture['inputEmail'])
            )
        );
    }

    public function testFindByEmailCaseInsensitiveReturnsCurrentAndLegacyDuplicates(): void
    {
        $fixture = $this->createLegacyFallbackFixture();
        $currentUser = $this->createUserWithEmail($fixture['normalizedEmail']);
        $legacyUser = $fixture['user'];

        $this->expectLegacyFallbackQueries(
            $fixture,
            $fixture['normalizedEmail'],
            [$currentUser],
            [$legacyUser]
        );

        $this->assertSame(
            [$currentUser, $legacyUser],
            iterator_to_array(
                $fixture['repository']->findByEmailCaseInsensitive($fixture['inputEmail'])
            )
        );
    }

    public function testFindByEmailsFallsBackToLegacyDocuments(): void
    {
        $fixture = $this->createLegacyFallbackFixture();

        $this->expectLegacyFallbackQueries($fixture, [$fixture['normalizedEmail']]);

        $this->assertSame(
            [$fixture['user']],
            iterator_to_array(
                $fixture['repository']->findByEmails([$fixture['inputEmail']])
            )
        );
    }

    public function testFindByEmailsReturnsCurrentAndLegacyDuplicates(): void
    {
        $fixture = $this->createLegacyFallbackFixture();
        $currentUser = $this->createUserWithEmail($fixture['normalizedEmail']);
        $legacyUser = $fixture['user'];

        $this->expectLegacyFallbackQueries(
            $fixture,
            [$fixture['normalizedEmail']],
            [$currentUser],
            [$legacyUser]
        );

        $this->assertSame(
            [$currentUser, $legacyUser],
            iterator_to_array(
                $fixture['repository']->findByEmails([$fixture['inputEmail']])
            )
        );
    }

    /** @return LegacyFallbackFixture */
    private function createLegacyFallbackFixture(): array
    {
        $email = $this->faker->unique()->email();
        $repositoryFixture = $this->createLegacyFallbackRepository();

        return [
            'inputEmail' => mb_strtoupper($email, 'UTF-8'),
            'normalizedEmail' => mb_strtolower($email, 'UTF-8'),
            'user' => $this->createUserWithEmail($email),
            ...$repositoryFixture,
        ];
    }

    /**
     * @return array{
     *     repository: MongoDBUserRepository,
     *     currentQueryBuilder: Builder,
     *     currentQuery: Query,
     *     legacyQueryBuilder: Builder,
     *     legacyQuery: Query
     * }
     */
    private function createLegacyFallbackRepository(): array
    {
        $currentQueryBuilder = $this->createMock(Builder::class);
        $currentQuery = $this->createMock(Query::class);
        $legacyQueryBuilder = $this->createMock(Builder::class);
        $legacyQuery = $this->createMock(Query::class);

        return [
            'repository' => $this->createRepositoryWithQueryBuilders(
                $currentQueryBuilder,
                $legacyQueryBuilder
            ),
            'currentQueryBuilder' => $currentQueryBuilder,
            'currentQuery' => $currentQuery,
            'legacyQueryBuilder' => $legacyQueryBuilder,
            'legacyQuery' => $legacyQuery,
        ];
    }

    private function createRepositoryWithQueryBuilders(
        Builder $currentQueryBuilder,
        Builder $legacyQueryBuilder
    ): MongoDBUserRepository {
        $this->registry
            ->expects($this->atLeastOnce())
            ->method('getManagerForClass')
            ->with(User::class)
            ->willReturn($this->documentManager);

        $repository = $this->getMockBuilder(MongoDBUserRepository::class)
            ->setConstructorArgs([
                $this->documentManager,
                $this->registry,
                self::BATCH_SIZE,
            ])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $repository->expects($this->exactly(2))
            ->method('createQueryBuilder')
            ->willReturnOnConsecutiveCalls($currentQueryBuilder, $legacyQueryBuilder);

        return $repository;
    }

    /**
     * @param LegacyFallbackFixture $fixture
     * @param list<string>|string $normalizedEmails
     * @param list<object> $currentQueryResult
     * @param list<object>|null $legacyQueryResult
     */
    private function expectLegacyFallbackQueries(
        array $fixture,
        array|string $normalizedEmails,
        array $currentQueryResult = [],
        ?array $legacyQueryResult = null
    ): void {
        $this->expectNormalizedEmailQuery(
            $fixture['currentQueryBuilder'],
            $fixture['currentQuery'],
            $normalizedEmails,
            $currentQueryResult
        );
        $this->expectLegacyEmailQuery(
            $fixture['legacyQueryBuilder'],
            $fixture['legacyQuery'],
            (array) $normalizedEmails,
            $legacyQueryResult ?? [$fixture['user']]
        );
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
        $this->expectNormalizedEmailField($queryBuilder);
        $this->expectNormalizedEmailConstraint($queryBuilder, $emails);
        $this->expectQueryExecution($queryBuilder, $query, $queryResult);
    }

    private function expectNormalizedEmailField(Builder $queryBuilder): void
    {
        $queryBuilder->expects($this->once())
            ->method('field')
            ->with('normalizedEmail')
            ->willReturnSelf();
    }

    /**
     * @param list<string>|string $emails
     */
    private function expectNormalizedEmailConstraint(
        Builder $queryBuilder,
        array|string $emails
    ): void {
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
     * @param list<string> $normalizedEmails
     * @param list<object> $queryResult
     */
    private function expectLegacyEmailQuery(
        Builder $queryBuilder,
        Query $query,
        array $normalizedEmails,
        array $queryResult
    ): void {
        $queryBuilder->expects($this->once())
            ->method('addAnd')
            ->with(
                $this->legacyNormalizedEmailExpression(),
                $this->legacyEmailExpression($normalizedEmails)
            )
            ->willReturnSelf();

        $this->expectQueryExecution($queryBuilder, $query, $queryResult);
    }

    /**
     * @return array{'$or': list<array{normalizedEmail: array{'$exists': false}|string}>}
     */
    private function legacyNormalizedEmailExpression(): array
    {
        return [
            '$or' => [
                ['normalizedEmail' => ['$exists' => false]],
                ['normalizedEmail' => ''],
            ],
        ];
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

    /**
     * @param list<string> $normalizedEmails
     *
     * @return array{'$or': list<array{email: array{'$regex': string, '$options': string}}>}
     */
    private function legacyEmailExpression(array $normalizedEmails): array
    {
        return [
            '$or' => array_map(
                static fn (string $normalizedEmail): array => [
                    'email' => [
                        '$regex' => '^' . preg_quote($normalizedEmail, '/') . '$',
                        '$options' => 'i',
                    ],
                ],
                $normalizedEmails
            ),
        ];
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
