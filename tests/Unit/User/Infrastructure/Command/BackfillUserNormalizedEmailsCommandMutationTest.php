<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Command;

/**
 * @psalm-type TestDocumentValue = object|array|string|int|float|bool|null
 * @psalm-type TestDocument = array<string, TestDocumentValue>
 * @psalm-type TestCursorDocument = object|TestDocument
 * @psalm-type TestBackfillId = object|int|string|null
 * @psalm-type UpdateOperation = array{
 *     updateOne: array{
 *         0: array{
 *             _id: TestBackfillId,
 *             '$or': list<array{normalizedEmail: array{'$exists': false}|string}>
 *         },
 *         1: array{'$set': array{normalizedEmail: string}}
 *     }
 * }
 */

use App\User\Application\Service\EmailNormalizer;
use function mb_strtoupper;
use MongoDB\BulkWriteResult;
use MongoDB\Collection;
use function sprintf;
use function substr_count;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class BackfillUserNormalizedEmailsCommandMutationTest extends
    BackfillUserNormalizedEmailsCommandTestCase
{
    public function testDuplicateFailureLimitsPreviewAndReportsCounts(): void
    {
        [$tester, $collection] = $this->createTesterWithCollection();

        $this->expectFindCalls($collection, $this->duplicateCandidates(6), []);
        $this->expectNoBulkWrite($collection);

        $this->assertSame(Command::FAILURE, $tester->execute([]));
        $display = $tester->getDisplay();

        $this->assertStringContainsString(
            'Backfill aborted: scanned 12 matched users, modified 0 users',
            $display
        );
        foreach (range(1, 5) as $position) {
            $this->assertStringContainsString($this->duplicateEmail($position), $display);
        }
        $this->assertStringNotContainsString($this->duplicateEmail(6), $display);
    }

    public function testCandidateCollectionSkipsUnreadableDocumentsAndContinues(): void
    {
        [$tester, $collection] = $this->createTesterWithCollection();
        $validCandidate = $this->candidate(7, 'valid-candidate@example.test');

        $this->expectFindCalls($collection, [
            (object) ['email' => 'ignored@example.test'],
            ['_id' => 'invalid-email', 'email' => 123],
            $validCandidate,
        ], []);
        $this->expectBulkWriteOperations(
            $collection,
            [$this->updateOperations([$validCandidate])],
            [1]
        );

        $this->assertSuccessfulBackfill($tester, 1, 1);
    }

    public function testExistingDocumentScanSkipsUnreadableDocumentsAndContinues(): void
    {
        [$tester, $collection] = $this->createTesterWithCollection();
        $candidate = $this->candidate('legacy-user', 'legacy-collision@example.test');
        $otherCandidate = $this->candidate('legacy-other-user', 'legacy-other@example.test');

        $this->expectFindCalls($collection, [$candidate, $otherCandidate], [
            (object) ['normalizedEmail' => 'legacy-collision@example.test'],
            ['normalizedEmail' => 'legacy-collision@example.test'],
            ['normalizedEmail' => 'legacy-collision@example.test'],
            ['normalizedEmail' => 'legacy-other@example.test'],
        ]);
        $this->expectNoBulkWrite($collection);

        $this->assertSame(Command::FAILURE, $tester->execute([]));
        $display = $tester->getDisplay();

        $this->assertStringContainsString('legacy-collision@example.test', $display);
        $this->assertStringContainsString('legacy-other@example.test', $display);
        $this->assertSame(1, substr_count($display, 'legacy-collision@example.test'));
    }

    public function testBackfillAccumulatesModifiedCountAcrossBatchesAndPreservesIntegerIds(): void
    {
        [$tester, $collection] = $this->createTesterWithCollection();
        $candidates = $this->integerCandidates(101);

        $this->expectFindCalls($collection, $candidates, []);
        $this->expectBulkWriteOperations(
            $collection,
            [
                $this->updateOperations(array_slice($candidates, 0, 100)),
                $this->updateOperations(array_slice($candidates, 100)),
            ],
            [100, 1]
        );

        $this->assertSuccessfulBackfill($tester, 101, 101);
    }

    /**
     * @param list<TestCursorDocument> $candidates
     * @param list<TestCursorDocument> $existing
     */
    private function expectFindCalls(
        Collection $collection,
        array $candidates,
        array $existing
    ): void {
        $collection->expects($this->exactly(2))
            ->method('find')
            ->willReturnCallback(
                fn (array $filter, array $options): ArrayCursor => $this
                    ->findCursor($filter, $options, $candidates, $existing)
            );
    }

    /**
     * @param array<string, array<string, bool>|list<string>> $filter
     * @param array<string, array<string, int>|int> $options
     * @param list<TestCursorDocument> $candidates
     * @param list<TestCursorDocument> $existing
     */
    private function findCursor(
        array $filter,
        array $options,
        array $candidates,
        array $existing
    ): ArrayCursor {
        if ($filter === $this->backfillFilter()) {
            $this->assertSame($this->candidateFindOptions(), $options);

            return new ArrayCursor($candidates);
        }

        $this->assertSame($this->existingFilter($candidates), $filter);
        $this->assertSame($this->existingFindOptions(), $options);

        return new ArrayCursor($existing);
    }

    /**
     * @param list<list<UpdateOperation>> $expectedBatches
     * @param list<int> $modifiedCounts
     */
    private function expectBulkWriteOperations(
        Collection $collection,
        array $expectedBatches,
        array $modifiedCounts
    ): void {
        $call = 0;
        $collection->expects($this->exactly(count($expectedBatches)))
            ->method('bulkWrite')
            ->willReturnCallback(
                function (
                    array $operations
                ) use (
                    $expectedBatches,
                    $modifiedCounts,
                    &$call
                ): BulkWriteResult {
                    $this->assertSame($expectedBatches[$call], $operations);
                    $result = $this->bulkWriteResult($modifiedCounts[$call]);
                    ++$call;

                    return $result;
                }
            );
    }

    private function bulkWriteResult(int $modifiedCount): BulkWriteResult
    {
        $result = $this->createMock(BulkWriteResult::class);
        $result->expects($this->once())
            ->method('getModifiedCount')
            ->willReturn($modifiedCount);

        return $result;
    }

    private function assertSuccessfulBackfill(
        CommandTester $tester,
        int $modified,
        int $matched
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
    private function duplicateCandidates(int $duplicateCount): array
    {
        $candidates = [];

        foreach (range(1, $duplicateCount) as $position) {
            $email = $this->duplicateEmail($position);
            $upperEmail = mb_strtoupper($email, 'UTF-8');
            $candidates[] = $this->candidate("duplicate-{$position}-a", $email);
            $candidates[] = $this->candidate("duplicate-{$position}-b", $upperEmail);
        }

        return $candidates;
    }

    /** @return list<TestDocument> */
    private function integerCandidates(int $count): array
    {
        $candidates = [];

        foreach (range(1, $count) as $position) {
            $candidates[] = $this->candidate(
                $position,
                sprintf('batch-%03d@example.test', $position)
            );
        }

        return $candidates;
    }

    /** @return array{_id: TestBackfillId, email: string} */
    private function candidate(object|int|string|null $id, string $email): array
    {
        return [
            '_id' => $id,
            'email' => $email,
        ];
    }

    private function duplicateEmail(int $position): string
    {
        return sprintf('duplicate-%d@example.test', $position);
    }

    /**
     * @param list<TestDocument> $candidates
     *
     * @return list<UpdateOperation>
     */
    private function updateOperations(array $candidates): array
    {
        $operations = [];

        foreach ($candidates as $candidate) {
            $email = $candidate['email'];
            self::assertIsString($email);
            $operations[] = [
                'updateOne' => [
                    $this->updateFilter($candidate['_id'] ?? null),
                    ['$set' => ['normalizedEmail' => (new EmailNormalizer())->normalize($email)]],
                ],
            ];
        }

        return $operations;
    }

    /**
     * @param list<TestCursorDocument> $candidates
     *
     * @return array{normalizedEmail: array{'$in': list<string>}}
     */
    private function existingFilter(array $candidates): array
    {
        $emails = [];
        $normalizer = new EmailNormalizer();

        foreach ($candidates as $candidate) {
            if (is_array($candidate) && is_string($candidate['email'] ?? null)) {
                $emails[] = $normalizer->normalize($candidate['email']);
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
