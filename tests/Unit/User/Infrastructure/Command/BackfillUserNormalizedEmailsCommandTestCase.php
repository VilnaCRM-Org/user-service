<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Command;

use App\Shared\Application\Normalizer\EmailNormalizer;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\User;
use App\User\Infrastructure\Command\BackfillUserNormalizedEmailsBackfiller;
use App\User\Infrastructure\Command\BackfillUserNormalizedEmailsCommand;
use App\User\Infrastructure\Command\BackfillUserNormalizedEmailsReportWriter;
use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\Collection;
use Symfony\Component\Console\Tester\CommandTester;

abstract class BackfillUserNormalizedEmailsCommandTestCase extends UnitTestCase
{
    /** @return array{0: CommandTester, 1: Collection} */
    protected function createTesterWithCollection(): array
    {
        $documentManager = $this->createMock(DocumentManager::class);
        $collection = $this->createMock(Collection::class);

        $this->expectUsersCollection($documentManager, $collection);

        return [
            new CommandTester(new BackfillUserNormalizedEmailsCommand(
                new BackfillUserNormalizedEmailsBackfiller(
                    $documentManager,
                    new EmailNormalizer()
                ),
                new BackfillUserNormalizedEmailsReportWriter($this->createJsonSerializer())
            )),
            $collection,
        ];
    }

    protected function expectNoBulkWrite(Collection $collection): void
    {
        $collection->expects($this->never())->method('bulkWrite');
    }

    /** @return array{'$or': list<array{normalizedEmail: array{'$exists': false}|string}>} */
    protected function backfillFilter(): array
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
    protected function candidateFindOptions(): array
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
    protected function existingFindOptions(): array
    {
        return [
            'projection' => ['_id' => 1, 'normalizedEmail' => 1],
            'batchSize' => 100,
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
}
