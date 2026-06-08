<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Command;

/**
 * @psalm-type TestDocumentValue = object|array|string|int|float|bool|null
 * @psalm-type TestDocument = array<string, TestDocumentValue>
 */

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Service\EmailNormalizer;
use App\User\Domain\Entity\User;
use App\User\Infrastructure\Command\BackfillUserNormalizedEmailsBackfiller;
use App\User\Infrastructure\Command\BackfillUserNormalizedEmailsCommand;
use App\User\Infrastructure\Command\BackfillUserNormalizedEmailsReportWriter;
use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\Collection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class BackfillUserNormalizedEmailsCommandReportTest extends UnitTestCase
{
    private const FAILING_REPORT_STREAM = 'backfillreportfailure';
    private const UNWRITABLE_REPORT_STREAM = 'backfillreportunwritable';

    public function testExecuteWritesDuplicateFailureReport(): void
    {
        [$tester, $collection] = $this->createTesterWithCollection();
        $reportFile = $this->temporaryReportFile();

        $this->expectFindCalls($collection, $this->duplicateCandidates(), []);
        $this->expectNoBulkWrite($collection);

        try {
            $this->assertSame(Command::FAILURE, $tester->execute(['--report-file' => $reportFile]));
            $this->assertStringContainsString('Backfill report written to ', $tester->getDisplay());
            $this->assertStringContainsString('Backfill aborted:', $tester->getDisplay());
            $this->assertDuplicateReportFile($reportFile);
        } finally {
            $this->removeReportFile($reportFile);
        }
    }

    public function testExecuteWritesDryRunReportWithZeroModifiedCount(): void
    {
        [$tester, $collection] = $this->createTesterWithCollection();
        $reportFile = $this->temporaryReportFile();

        $this->expectFindCalls(
            $collection,
            [['email' => 'USER@example.com', '_id' => 'user-1']],
            []
        );
        $this->expectNoBulkWrite($collection);

        try {
            $this->assertSame(Command::SUCCESS, $tester->execute([
                '--dry-run' => true,
                '--report-file' => $reportFile,
            ]));
            $this->assertSuccessReportFile($reportFile, 1, 0, true);
        } finally {
            $this->removeReportFile($reportFile);
        }
    }

    public function testExecuteIgnoresEmptyReportFileOption(): void
    {
        [$tester, $collection] = $this->createTesterWithCollection();

        $this->expectFindCalls($collection, [], []);
        $this->expectNoBulkWrite($collection);

        $this->assertSame(Command::SUCCESS, $tester->execute(['--report-file' => '']));
        $this->assertStringNotContainsString('Backfill report written to ', $tester->getDisplay());
    }

    public function testExecuteReturnsFailureWhenReportFileCannotBeWritten(): void
    {
        [$tester, $collection] = $this->createTesterWithCollection();
        $reportFile = sprintf(
            '%s/missing-%s/report.json',
            sys_get_temp_dir(),
            $this->faker->uuid()
        );

        $this->expectFindCalls($collection, [], []);
        $this->expectNoBulkWrite($collection);

        $this->assertSame(Command::FAILURE, $tester->execute(['--report-file' => $reportFile]));
        $this->assertStringContainsString(
            'Backfill report directory does not exist for',
            $tester->getDisplay()
        );
        $this->assertStringContainsString($reportFile, $tester->getDisplay());
    }

    public function testExecuteReturnsFailureWhenReportDirectoryIsNotWritable(): void
    {
        [$tester, $collection] = $this->createTesterWithCollection();
        $reportFile = sprintf('%s://directory/report.json', self::UNWRITABLE_REPORT_STREAM);

        $this->expectFindCalls($collection, [], []);
        $this->expectNoBulkWrite($collection);
        $this->registerReportStream(self::UNWRITABLE_REPORT_STREAM, 0040555);

        try {
            $this->assertSame(Command::FAILURE, $tester->execute(['--report-file' => $reportFile]));
            $this->assertStringContainsString(
                'Backfill report directory is not writable for',
                $tester->getDisplay()
            );
            $this->assertStringContainsString($reportFile, $tester->getDisplay());
        } finally {
            $this->unregisterReportStream(self::UNWRITABLE_REPORT_STREAM);
        }
    }

    public function testExecuteReturnsFailureWhenReportWriteOperationFails(): void
    {
        [$tester, $collection] = $this->createTesterWithCollection();
        $reportFile = sprintf('%s://directory/report.json', self::FAILING_REPORT_STREAM);

        $this->expectFindCalls($collection, [], []);
        $this->expectNoBulkWrite($collection);
        $this->registerReportStream(self::FAILING_REPORT_STREAM, 0040777);

        try {
            $this->assertSame(Command::FAILURE, $tester->execute(['--report-file' => $reportFile]));
            $this->assertStringContainsString(
                'Backfill report could not be written to',
                $tester->getDisplay()
            );
            $this->assertStringContainsString($reportFile, $tester->getDisplay());
        } finally {
            $this->unregisterReportStream(self::FAILING_REPORT_STREAM);
        }
    }

    /** @return array{0: CommandTester, 1: Collection} */
    private function createTesterWithCollection(): array
    {
        $documentManager = $this->createMock(DocumentManager::class);
        $collection = $this->createMock(Collection::class);

        $documentManager->expects($this->any())
            ->method('getDocumentCollection')
            ->with(User::class)
            ->willReturn($collection);

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

    /**
     * @param list<TestDocument> $candidates
     * @param list<TestDocument> $existing
     */
    private function expectFindCalls(
        Collection $collection,
        array $candidates,
        array $existing
    ): void {
        $expectedCalls = $candidates === [] ? 1 : 2;

        $collection->expects($this->exactly($expectedCalls))
            ->method('find')
            ->willReturnOnConsecutiveCalls(
                new ArrayCursor($candidates),
                new ArrayCursor($existing)
            );
    }

    private function expectNoBulkWrite(Collection $collection): void
    {
        $collection->expects($this->never())->method('bulkWrite');
    }

    /** @return list<TestDocument> */
    private function duplicateCandidates(): array
    {
        return [
            ['_id' => 'user-1', 'email' => 'USER@example.com'],
            ['_id' => 'user-2', 'email' => 'user@example.com'],
        ];
    }

    private function temporaryReportFile(): string
    {
        $reportFile = tempnam(sys_get_temp_dir(), 'backfill-normalized-email-');
        $this->assertIsString($reportFile);

        return $reportFile;
    }

    private function removeReportFile(string $reportFile): void
    {
        if (is_file($reportFile)) {
            unlink($reportFile);
        }
    }

    private function unregisterReportStream(string $protocol): void
    {
        $this->assertTrue(stream_wrapper_unregister($protocol));
    }

    private function registerReportStream(string $protocol, int $directoryMode): void
    {
        BackfillReportFailureStream::useDirectoryMode($directoryMode);
        $this->assertStreamWrapperContract($directoryMode);
        $this->assertTrue(stream_wrapper_register(
            $protocol,
            BackfillReportFailureStream::class
        ));
    }

    private function assertStreamWrapperContract(int $directoryMode): void
    {
        $stream = new BackfillReportFailureStream();

        $this->assertTrue($stream->__call('stream_open', []));
        $this->assertFalse($stream->__call('stream_write', []));
        $this->assertSame(['mode' => $directoryMode], $stream->__call('url_stat', []));
    }

    private function assertDuplicateReportFile(string $reportFile): void
    {
        $report = $this->decodedReport($reportFile);

        $this->assertStringContainsString(
            "\n    \"status\": \"duplicate_failure\"",
            $report['raw']
        );
        $this->assertStringEndsWith(PHP_EOL, $report['raw']);
        $this->assertSame('duplicate_failure', $report['decoded']['status']);
        $this->assertSame(2, $report['decoded']['matched']);
        $this->assertSame(0, $report['decoded']['modified']);
        $this->assertFalse($report['decoded']['dryRun']);
        $this->assertSame(['user@example.com'], $report['decoded']['duplicates']);
        $this->assertIsString($report['decoded']['generatedAt']);
    }

    private function assertSuccessReportFile(
        string $reportFile,
        int $matched,
        int $modified,
        bool $dryRun
    ): void {
        $report = $this->decodedReport($reportFile);

        $this->assertSame('success', $report['decoded']['status']);
        $this->assertSame($matched, $report['decoded']['matched']);
        $this->assertSame($modified, $report['decoded']['modified']);
        $this->assertSame($dryRun, $report['decoded']['dryRun']);
        $this->assertSame([], $report['decoded']['duplicates']);
    }

    /**
     * @return array{
     *     raw: string,
     *     decoded: array{
     *         generatedAt: string,
     *         status: string,
     *         matched: int,
     *         modified: int,
     *         dryRun: bool,
     *         duplicates: list<string>
     *     }
     * }
     */
    private function decodedReport(string $reportFile): array
    {
        $contents = file_get_contents($reportFile);
        $this->assertIsString($contents);
        $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($decoded);

        return [
            'raw' => $contents,
            'decoded' => $decoded,
        ];
    }
}
