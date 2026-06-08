<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Command;

/**
 * @psalm-type TestDocumentValue = object|array|string|int|float|bool|null
 * @psalm-type TestDocument = array<string, TestDocumentValue>
 * @psalm-type TestBackfillId = object|int|string|null
 */

use App\User\Application\Service\EmailNormalizer;
use ArrayObject;
use function mb_strtoupper;
use MongoDB\BulkWriteResult;
use MongoDB\Collection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class BackfillUserNormalizedEmailsCommandTest extends
    BackfillUserNormalizedEmailsCommandTestCase
{
    public function testExecuteBackfillsMissingAndEmptyNormalizedEmailsWithPhpNormalizer(): void
    {
        [$tester, $collection] = $this->createTesterWithCollection();
        $result = $this->createMock(BulkWriteResult::class);
        $unicodeEmail = sprintf("\u{0104}\u{017D}@%s", $this->faker->safeEmailDomain());
        $candidates = $this->successfulCandidates($this->faker->safeEmail(), $unicodeEmail);

        $this->expectFindCalls($collection, $candidates, []);
        $this->expectSuccessfulBulkWrite($collection, $result, $candidates);
        $this->assertSuccessfulBackfill($tester);
    }

    public function testExecuteAbortsWhenCandidatesNormalizeToDuplicateEmail(): void
    {
        [$tester, $collection] = $this->createTesterWithCollection();
        $email = $this->faker->safeEmail();
        $normalizedEmail = $this->normalizeEmail($email);

        $this->expectFindCalls(
            $collection,
            [
                ['_id' => $this->faker->uuid(), 'email' => $this->uppercaseEmail($email)],
                ['_id' => $this->faker->uuid(), 'email' => $email],
            ],
            []
        );
        $this->expectNoBulkWrite($collection);

        $this->assertDuplicateFailure($tester, $normalizedEmail, 2);
    }

    public function testExecuteAbortsWhenExistingNormalizedEmailWouldCollide(): void
    {
        [$tester, $collection] = $this->createTesterWithCollection();
        $email = $this->faker->safeEmail();
        $normalizedEmail = $this->normalizeEmail($email);

        $this->expectFindCalls(
            $collection,
            [
                ['_id' => $this->faker->uuid(), 'email' => $this->uppercaseEmail($email)],
            ],
            [
                ['_id' => $this->faker->uuid(), 'normalizedEmail' => $normalizedEmail],
            ]
        );
        $this->expectNoBulkWrite($collection);

        $this->assertDuplicateFailure($tester, $normalizedEmail, 1);
    }

    public function testExecuteDryRunScansWithoutBulkWrite(): void
    {
        [$tester, $collection] = $this->createTesterWithCollection();
        $unicodeEmail = sprintf("\u{0104}\u{017D}@%s", $this->faker->safeEmailDomain());
        $candidates = $this->successfulCandidates($this->faker->safeEmail(), $unicodeEmail);

        $this->expectFindCalls($collection, $candidates, []);
        $this->expectNoBulkWrite($collection);

        $this->assertSame(Command::SUCCESS, $tester->execute(['--dry-run' => true]));
        $this->assertStringContainsString(
            'Dry run completed: 2 matched users would be backfilled; 0 users modified.',
            $tester->getDisplay()
        );
        $this->assertStringNotContainsString(
            'Backfilled normalized emails',
            $tester->getDisplay()
        );
    }

    public function testExecuteSkipsUnreadableCandidatesAndSucceedsWithoutUpdates(): void
    {
        [$tester, $collection] = $this->createTesterWithCollection();

        $collection->expects($this->once())
            ->method('find')
            ->with($this->backfillFilter(), $this->candidateFindOptions())
            ->willReturn(new ArrayCursor([
                (object) ['email' => $this->faker->safeEmail()],
                ['_id' => $this->faker->uuid()],
                ['_id' => $this->faker->uuid(), 'email' => 123],
            ]));
        $this->expectNoBulkWrite($collection);

        $this->assertSuccessfulBackfill($tester, 0, 0);
    }

    public function testExecuteSupportsArrayAccessBackfillDocuments(): void
    {
        [$tester, $collection] = $this->createTesterWithCollection();
        $result = $this->createMock(BulkWriteResult::class);
        $email = $this->faker->safeEmail();
        $candidateEmail = sprintf(' %s ', $this->uppercaseEmail($email));
        $normalizedEmail = $this->normalizeEmail($candidateEmail);
        $candidate = new ArrayObject([
            '_id' => $this->faker->boolean(),
            'email' => $candidateEmail,
        ]);
        $operation = [
            'updateOne' => [
                $this->updateFilter(null),
                ['$set' => ['normalizedEmail' => $normalizedEmail]],
            ],
        ];

        $this->expectArrayAccessBackfillFindCalls($collection, $candidate, $normalizedEmail);
        $this->expectBulkWrite($collection, $result, [$operation]);
        $result->expects($this->once())->method('getModifiedCount')->willReturn(1);

        $this->assertSuccessfulBackfill($tester, 1, 1);
    }

    private function assertDuplicateFailure(
        CommandTester $tester,
        string $normalizedEmail,
        int $matched
    ): void {
        $this->assertSame(Command::FAILURE, $tester->execute([]));
        $this->assertStringContainsString(
            sprintf(
                'Backfill aborted: scanned %d matched users, modified 0 users',
                $matched
            ),
            $tester->getDisplay()
        );
        $this->assertStringContainsString($normalizedEmail, $tester->getDisplay());
    }

    private function assertSuccessfulBackfill(
        CommandTester $tester,
        int $modified = 2,
        int $matched = 2
    ): void {
        $this->assertSame(Command::SUCCESS, $tester->execute([]));
        $this->assertStringContainsString(
            sprintf(
                'Backfilled normalized emails for %d of %d matched users.',
                $modified,
                $matched
            ),
            $tester->getDisplay()
        );
    }

    /** @return list<TestDocument> */
    private function successfulCandidates(string $email, string $unicodeEmail): array
    {
        return [
            [
                '_id' => $this->faker->uuid(),
                'email' => sprintf(' %s ', $this->uppercaseEmail($email)),
            ],
            ['_id' => $this->faker->uuid(), 'email' => $unicodeEmail],
        ];
    }

    /** @param list<TestDocument> $candidates */
    private function expectSuccessfulBulkWrite(
        Collection $collection,
        BulkWriteResult $result,
        array $candidates
    ): void {
        $this->expectBulkWrite(
            $collection,
            $result,
            $this->successfulBackfillOperations($candidates)
        );
        $result->expects($this->once())->method('getModifiedCount')->willReturn(2);
    }

    private function expectArrayAccessBackfillFindCalls(
        Collection $collection,
        ArrayObject $candidate,
        string $normalizedEmail
    ): void {
        $collection->expects($this->exactly(2))
            ->method('find')
            ->willReturnCallback(
                fn (array $filter, array $options): ArrayCursor => $this
                    ->arrayAccessBackfillCursor($filter, $options, $candidate, $normalizedEmail)
            );
    }

    /**
     * @param array<
     *     string,
     *     array<string, list<string>>
     *     |list<array<string, array<string, bool>|string>>
     * > $filter
     * @param array<string, array<string, int>|int> $options
     */
    private function arrayAccessBackfillCursor(
        array $filter,
        array $options,
        ArrayObject $candidate,
        string $normalizedEmail
    ): ArrayCursor {
        if ($filter === $this->backfillFilter()) {
            $this->assertSame($this->candidateFindOptions(), $options);

            return new ArrayCursor([$candidate]);
        }

        $this->assertSame([
            'normalizedEmail' => [
                '$in' => [$normalizedEmail],
            ],
        ], $filter);
        $this->assertSame($this->existingFindOptions(), $options);

        return new ArrayCursor([
            (object) ['normalizedEmail' => $normalizedEmail],
            ['_id' => $this->faker->uuid()],
        ]);
    }

    /**
     * @param list<TestDocument> $candidates
     *
     * @return list<array{
     *     updateOne: array{
     *         0: array{
     *             _id: TestBackfillId,
     *             '$or': list<array{normalizedEmail: array{'$exists': false}|string}>
     *         },
     *         1: array{'$set': array{normalizedEmail: string}}
     *     }
     * }>
     */
    private function successfulBackfillOperations(array $candidates): array
    {
        $firstEmail = $candidates[0]['email'] ?? null;
        $secondEmail = $candidates[1]['email'] ?? null;

        self::assertIsString($firstEmail);
        self::assertIsString($secondEmail);

        return [
            [
                'updateOne' => [
                    $this->updateFilter($candidates[0]['_id'] ?? null),
                    ['$set' => ['normalizedEmail' => $this->normalizeEmail($firstEmail)]],
                ],
            ],
            [
                'updateOne' => [
                    $this->updateFilter($candidates[1]['_id'] ?? null),
                    ['$set' => ['normalizedEmail' => $this->normalizeEmail($secondEmail)]],
                ],
            ],
        ];
    }

    private function uppercaseEmail(string $email): string
    {
        return mb_strtoupper($email, 'UTF-8');
    }

    private function normalizeEmail(string $email): string
    {
        return (new EmailNormalizer())->normalize($email);
    }

    /**
     * @param list<TestDocument> $candidates
     * @param list<TestDocument> $existing
     */
    private function expectFindCalls(
        Collection $collection,
        array $candidates,
        array $existing
    ): void {
        $collection->expects($this->exactly(2))
            ->method('find')
            ->willReturnCallback(
                function (array $filter, array $options) use ($candidates, $existing): ArrayCursor {
                    if ($filter === $this->backfillFilter()) {
                        $this->assertSame($this->candidateFindOptions(), $options);

                        return new ArrayCursor($candidates);
                    }

                    $this->assertSame($this->existingFilter($candidates), $filter);
                    $this->assertSame($this->existingFindOptions(), $options);

                    return new ArrayCursor($existing);
                }
            );
    }

    /**
     * @param list<array{
     *     updateOne: array{
     *         0: array{
     *             _id: TestBackfillId,
     *             '$or': list<array{normalizedEmail: array{'$exists': false}|string}>
     *         },
     *         1: array{'$set': array{normalizedEmail: string}}
     *     }
     * }> $operations
     */
    private function expectBulkWrite(
        Collection $collection,
        BulkWriteResult $result,
        array $operations
    ): void {
        $collection->expects($this->once())
            ->method('bulkWrite')
            ->with($operations)
            ->willReturn($result);
    }

    /**
     * @param list<TestDocument> $candidates
     *
     * @return array{normalizedEmail: array{'$in': list<string>}}
     */
    private function existingFilter(array $candidates): array
    {
        $emails = [];
        $normalizer = new EmailNormalizer();

        foreach ($candidates as $candidate) {
            $email = $candidate['email'] ?? null;

            if (is_string($email)) {
                $emails[] = $normalizer->normalize($email);
            }
        }

        return [
            'normalizedEmail' => [
                '$in' => array_values(array_unique($emails)),
            ],
        ];
    }

    /**
     * @return array{
     *     _id: TestBackfillId,
     *     '$or': list<array{normalizedEmail: array{'$exists': false}|string}>
     * }
     */
    private function updateFilter(object|int|string|null $id): array
    {
        return [
            '_id' => $id,
            ...$this->backfillFilter(),
        ];
    }
}
