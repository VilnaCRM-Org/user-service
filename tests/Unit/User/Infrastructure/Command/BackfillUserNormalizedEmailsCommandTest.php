<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Command;

/**
 * @psalm-type TestDocumentValue = object|array|string|int|float|bool|null
 * @psalm-type TestDocument = array<string, TestDocumentValue>
 * @psalm-type TestBackfillId = object|int|string|null
 */

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Service\EmailNormalizer;
use App\User\Domain\Entity\User;
use App\User\Infrastructure\Command\BackfillUserNormalizedEmailsCommand;
use ArrayObject;
use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\Int64;
use MongoDB\BulkWriteResult;
use MongoDB\Collection;
use MongoDB\Driver\Server;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class BackfillUserNormalizedEmailsCommandTest extends UnitTestCase
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

        $this->assertDuplicateFailure($tester, 'user@example.com');
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

        $this->assertDuplicateFailure($tester, 'legacy@example.com');
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

    public function testExecuteHandlesArrayAccessCandidatesAndSkipsUnreadableExistingDocuments(): void
    {
        [$tester, $collection] = $this->createTesterWithCollection();
        $result = $this->createMock(BulkWriteResult::class);
        $candidate = new ArrayObject([
            '_id' => true,
            'email' => ' ARRAY@example.COM ',
        ]);

        $collection->expects($this->exactly(2))
            ->method('find')
            ->willReturnCallback(
                function (array $filter, array $options) use ($candidate): ArrayCursor {
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
            );
        $this->expectBulkWrite(
            $collection,
            $result,
            [
                [
                    'updateOne' => [
                        $this->updateFilter(null),
                        ['$set' => ['normalizedEmail' => 'array@example.com']],
                    ],
                ],
            ]
        );
        $result->expects($this->once())->method('getModifiedCount')->willReturn(1);

        $this->assertSuccessfulBackfill($tester, 1, 1);
    }

    public function testArrayCursorReportsDeadStateForEmptyDocuments(): void
    {
        $cursor = new ArrayCursor([]);

        $cursor->setTypeMap([]);

        $this->assertTrue($cursor->isDead());
        $this->assertSame([], $cursor->toArray());
    }

    public function testArrayCursorIdMetadataIsUnavailable(): void
    {
        $this->expectException(RuntimeException::class);

        self::assertInstanceOf(Int64::class, (new ArrayCursor([]))->getId());
    }

    public function testArrayCursorServerMetadataIsUnavailable(): void
    {
        $this->expectException(RuntimeException::class);

        self::assertInstanceOf(Server::class, (new ArrayCursor([]))->getServer());
    }

    /** @return array{0: CommandTester, 1: Collection} */
    private function createTesterWithCollection(): array
    {
        $documentManager = $this->createMock(DocumentManager::class);
        $collection = $this->createMock(Collection::class);

        $this->expectUsersCollection($documentManager, $collection);

        return [
            new CommandTester(new BackfillUserNormalizedEmailsCommand(
                $documentManager,
                new EmailNormalizer()
            )),
            $collection,
        ];
    }

    private function assertDuplicateFailure(
        CommandTester $tester,
        string $normalizedEmail
    ): void {
        $this->assertSame(Command::FAILURE, $tester->execute([]));
        $this->assertStringContainsString(
            'Backfill aborted because duplicate normalized emails were found:',
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

    private function expectUsersCollection(
        DocumentManager $documentManager,
        Collection $collection
    ): void {
        $documentManager->expects($this->any())
            ->method('getDocumentCollection')
            ->with(User::class)
            ->willReturn($collection);
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

    private function expectNoBulkWrite(Collection $collection): void
    {
        $collection->expects($this->never())->method('bulkWrite');
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

    /** @return array{'$or': list<array{normalizedEmail: array{'$exists': false}|string}>} */
    private function backfillFilter(): array
    {
        return [
            '$or' => [
                ['normalizedEmail' => ['$exists' => false]],
                ['normalizedEmail' => ''],
            ],
        ];
    }

    /**
     * @return array{
     *     projection: array{_id: int, email: int},
     *     sort: array{_id: int},
     *     batchSize: int
     * }
     */
    private function candidateFindOptions(): array
    {
        return [
            'projection' => ['_id' => 1, 'email' => 1],
            'sort' => ['_id' => 1],
            'batchSize' => 100,
        ];
    }

    /**
     * @return array{
     *     projection: array{_id: int, normalizedEmail: int},
     *     batchSize: int
     * }
     */
    private function existingFindOptions(): array
    {
        return [
            'projection' => ['_id' => 1, 'normalizedEmail' => 1],
            'batchSize' => 100,
        ];
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
