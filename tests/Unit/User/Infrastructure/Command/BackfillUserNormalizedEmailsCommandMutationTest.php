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

use App\Shared\Application\Normalizer\EmailNormalizer;

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
        $duplicateEmails = $this->duplicateEmails(6);

        $this->expectFindCalls($collection, $this->duplicateCandidates($duplicateEmails), []);
        $this->expectNoBulkWrite($collection);

        $this->assertSame(Command::FAILURE, $tester->execute([]));
        $display = $tester->getDisplay();

        $this->assertStringContainsString(
            'Backfill aborted: scanned 12 matched users, modified 0 users',
            $display
        );
        foreach (array_slice($duplicateEmails, 0, 5) as $email) {
            $this->assertStringContainsString($this->normalizeEmail($email), $display);
        }
        $this->assertStringNotContainsString(
            $this->normalizeEmail($duplicateEmails[5]),
            $display
        );
    }

    public function testCandidateCollectionSkipsUnreadableDocumentsAndContinues(): void
    {
        [$tester, $collection] = $this->createTesterWithCollection();
        $validCandidate = $this->candidate(
            $this->faker->uuid(),
            $this->faker->safeEmail()
        );

        $this->expectFindCalls($collection, [
            (object) ['email' => $this->faker->safeEmail()],
            ['_id' => $this->faker->uuid(), 'email' => 123],
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
        $email = $this->generatedEmail();
        $otherEmail = $this->generatedEmail();
        $normalizedEmail = $this->normalizeEmail($email);
        $otherNormalizedEmail = $this->normalizeEmail($otherEmail);
        $candidate = $this->candidate($this->faker->uuid(), $email);
        $otherCandidate = $this->candidate($this->faker->uuid(), $otherEmail);

        $this->expectFindCalls($collection, [$candidate, $otherCandidate], [
            (object) ['normalizedEmail' => $normalizedEmail],
            ['normalizedEmail' => $normalizedEmail],
            ['normalizedEmail' => $normalizedEmail],
            ['normalizedEmail' => $otherNormalizedEmail],
        ]);
        $this->expectNoBulkWrite($collection);

        $this->assertSame(Command::FAILURE, $tester->execute([]));
        $display = $tester->getDisplay();

        $this->assertStringContainsString($normalizedEmail, $display);
        $this->assertStringContainsString($otherNormalizedEmail, $display);
        $this->assertSame(1, substr_count($display, $normalizedEmail));
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

    /** @return list<string> */
    private function duplicateEmails(int $duplicateCount): array
    {
        $emails = [];

        for ($index = 0; $index < $duplicateCount; ++$index) {
            $emails[] = $this->generatedEmail();
        }

        return $emails;
    }

    /**
     * @param list<string> $emails
     *
     * @return list<TestDocument>
     */
    private function duplicateCandidates(array $emails): array
    {
        $candidates = [];

        foreach ($emails as $email) {
            $upperEmail = mb_strtoupper($email, 'UTF-8');
            $candidates[] = $this->candidate($this->faker->uuid(), $email);
            $candidates[] = $this->candidate($this->faker->uuid(), $upperEmail);
        }

        return $candidates;
    }

    /** @return list<TestDocument> */
    private function integerCandidates(int $count): array
    {
        $candidates = [];

        for ($index = 0; $index < $count; ++$index) {
            $candidates[] = $this->candidate(
                $this->faker->unique()->numberBetween(1, 1_000_000),
                $this->faker->unique()->safeEmail()
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

    private function normalizeEmail(string $email): string
    {
        return (new EmailNormalizer())->normalize($email);
    }

    private function generatedEmail(): string
    {
        return sprintf('%s@%s', $this->faker->uuid(), $this->faker->safeEmailDomain());
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
