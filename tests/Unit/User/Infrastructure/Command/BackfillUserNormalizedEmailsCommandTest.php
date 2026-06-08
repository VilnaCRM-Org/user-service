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
        $unicodeEmail = "\u{0104}\u{017D}@example.COM";
        $normalizedUnicodeEmail = "\u{0105}\u{017E}@example.com";

        $this->expectFindCalls($collection, $this->successfulCandidates($unicodeEmail), []);
        $this->expectSuccessfulBulkWrite($collection, $result, $normalizedUnicodeEmail);
        $this->assertSuccessfulBackfill($tester);
    }

    public function testExecuteAbortsWhenCandidatesNormalizeToDuplicateEmail(): void
    {
        [$tester, $collection] = $this->createTesterWithCollection();

        $this->expectFindCalls(
            $collection,
            [
                ['_id' => 'user-1', 'email' => 'USER@example.com'],
                ['_id' => 'user-2', 'email' => 'user@example.com'],
            ],
            []
        );
        $this->expectNoBulkWrite($collection);

        $this->assertDuplicateFailure($tester, 'user@example.com', 2);
    }

    public function testExecuteAbortsWhenExistingNormalizedEmailWouldCollide(): void
    {
        [$tester, $collection] = $this->createTesterWithCollection();

        $this->expectFindCalls(
            $collection,
            [
                ['_id' => 'legacy-user', 'email' => 'LEGACY@example.com'],
            ],
            [
                ['_id' => 'current-user', 'normalizedEmail' => 'legacy@example.com'],
            ]
        );
        $this->expectNoBulkWrite($collection);

        $this->assertDuplicateFailure($tester, 'legacy@example.com', 1);
    }

    public function testExecuteDryRunScansWithoutBulkWrite(): void
    {
        [$tester, $collection] = $this->createTesterWithCollection();
        $unicodeEmail = "\u{0104}\u{017D}@example.COM";

        $this->expectFindCalls($collection, $this->successfulCandidates($unicodeEmail), []);
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
                (object) ['email' => 'ignored@example.com'],
                ['_id' => 'missing-email'],
                ['_id' => 'invalid-email', 'email' => 123],
            ]));
        $this->expectNoBulkWrite($collection);

        $this->assertSuccessfulBackfill($tester, 0, 0);
    }

    public function testExecuteSupportsArrayAccessBackfillDocuments(): void
    {
        [$tester, $collection] = $this->createTesterWithCollection();
        $result = $this->createMock(BulkWriteResult::class);
        $candidate = new ArrayObject([
            '_id' => true,
            'email' => ' ARRAY@example.COM ',
        ]);
        $operation = [
            'updateOne' => [
                $this->updateFilter(null),
                ['$set' => ['normalizedEmail' => 'array@example.com']],
            ],
        ];

        $this->expectArrayAccessBackfillFindCalls($collection, $candidate);
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
    private function successfulCandidates(string $unicodeEmail): array
    {
        return [
            ['_id' => 'user-1', 'email' => ' USER@example.COM '],
            ['_id' => 'user-2', 'email' => $unicodeEmail],
        ];
    }

    private function expectSuccessfulBulkWrite(
        Collection $collection,
        BulkWriteResult $result,
        string $normalizedUnicodeEmail
    ): void {
        $this->expectBulkWrite(
            $collection,
            $result,
            $this->successfulBackfillOperations($normalizedUnicodeEmail)
        );
        $result->expects($this->once())->method('getModifiedCount')->willReturn(2);
    }

    private function expectArrayAccessBackfillFindCalls(
        Collection $collection,
        ArrayObject $candidate
    ): void {
        $collection->expects($this->exactly(2))
            ->method('find')
            ->willReturnCallback(
                fn (array $filter, array $options): ArrayCursor => $this
                    ->arrayAccessBackfillCursor($filter, $options, $candidate)
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
        ArrayObject $candidate
    ): ArrayCursor {
        if ($filter === $this->backfillFilter()) {
            $this->assertSame($this->candidateFindOptions(), $options);

            return new ArrayCursor([$candidate]);
        }

        $this->assertSame([
            'normalizedEmail' => [
                '$in' => ['array@example.com'],
            ],
        ], $filter);
        $this->assertSame($this->existingFindOptions(), $options);

        return new ArrayCursor([
            (object) ['normalizedEmail' => 'array@example.com'],
            ['_id' => 'existing-without-normalized-email'],
        ]);
    }

    /**
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
    private function successfulBackfillOperations(string $normalizedUnicodeEmail): array
    {
        return [
            [
                'updateOne' => [
                    $this->updateFilter('user-1'),
                    ['$set' => ['normalizedEmail' => 'user@example.com']],
                ],
            ],
            [
                'updateOne' => [
                    $this->updateFilter('user-2'),
                    ['$set' => ['normalizedEmail' => $normalizedUnicodeEmail]],
                ],
            ],
        ];
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
