<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Repository;

use App\User\Infrastructure\Repository\MongoDBUserRepository;
use Doctrine\ODM\MongoDB\Query\Builder;
use Doctrine\ODM\MongoDB\Query\Query;

use function is_string;
use function mb_strtolower;
use function mb_strtoupper;

final class MongoDBUserRepositoryFindByEmailsTest extends MongoDBUserRepositoryTestCase
{
    public function testFindByEmailCaseInsensitiveReturnsUser(): void
    {
        $caseInsensitiveEmail = $this->faker->unique()->email();
        $inputEmail = '  ' . mb_strtoupper($caseInsensitiveEmail, 'UTF-8') . '  ';
        $user = $this->createUserWithEmail($caseInsensitiveEmail);
        [$repository, $queryBuilder, $query] = $this->createQueryBuilderRepository();

        $this->expectFindByEmailCaseInsensitiveQueryWithEmptyLegacyFallback(
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

        $this->expectFindByEmailsQuery(
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

        $this->expectFindByEmailsQuery(
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

        $this->expectFindByEmailsQuery(
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

        $this->expectFindByEmailsQuery(
            $repository,
            $queryBuilder,
            $query,
            [$email],
            [$user, new \stdClass()]
        );

        $this->assertSame([$user], iterator_to_array($repository->findByEmails([$email])));
    }

    /**
     * @return array{MongoDBUserRepository, Builder, Query}
     */
    private function createQueryBuilderRepository(): array
    {
        $queryBuilder = $this->createMock(Builder::class);
        $query = $this->createMock(Query::class);
        $repository = $this->createRepositoryMock(['createQueryBuilder']);

        return [$repository, $queryBuilder, $query];
    }

    /**
     * @param list<string>|string $emails
     * @param list<object> $queryResult
     */
    private function expectFindByEmailCaseInsensitiveQueryWithEmptyLegacyFallback(
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
}
