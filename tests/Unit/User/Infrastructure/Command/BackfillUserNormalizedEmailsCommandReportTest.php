<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Command;

/**
 * @psalm-type TestDocumentValue = object|array|string|int|float|bool|null
 * @psalm-type TestDocument = array<string, TestDocumentValue>
 */

use App\User\Application\Service\EmailNormalizer;
use function mb_strtoupper;
use MongoDB\Collection;
use Symfony\Component\Console\Command\Command;

final class BackfillUserNormalizedEmailsCommandReportTest extends
    BackfillUserNormalizedEmailsCommandTestCase
{
    private const FAILING_REPORT_STREAM = 'backfillreportfailure';
    private const UNWRITABLE_REPORT_STREAM = 'backfillreportunwritable';

    public function testExecuteWritesDuplicateFailureReport(): void
    {
        [$tester, $collection] = $this->createTesterWithCollection();
        $reportFile = $this->temporaryReportFile();
        $duplicateEmail = $this->faker->safeEmail();
        $normalizedDuplicateEmail = $this->normalizeEmail($duplicateEmail);

        $this->expectFindCalls($collection, $this->duplicateCandidates($duplicateEmail), []);
        $this->expectNoBulkWrite($collection);

        try {
            $this->assertSame(Command::FAILURE, $tester->execute(['--report-file' => $reportFile]));
            $this->assertStringContainsString('Backfill report written to ', $tester->getDisplay());
            $this->assertStringContainsString('Backfill aborted:', $tester->getDisplay());
            $this->assertDuplicateReportFile($reportFile, $normalizedDuplicateEmail);
        } finally {
            $this->removeReportFile($reportFile);
        }
    }

    public function testExecuteWritesDryRunReportWithZeroModifiedCount(): void
    {
        [$tester, $collection] = $this->createTesterWithCollection();
        $reportFile = $this->temporaryReportFile();

        $this->expectFindCalls($collection, $this->dryRunCandidates(), []);
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
        $this->assertReportStreamFailure(
            self::UNWRITABLE_REPORT_STREAM,
            0040555,
            'Backfill report directory is not writable for'
        );
    }

    public function testExecuteReturnsFailureWhenReportWriteOperationFails(): void
    {
        $this->assertReportStreamFailure(
            self::FAILING_REPORT_STREAM,
            0040777,
            'Backfill report could not be written to'
        );
    }

    private function assertReportStreamFailure(
        string $protocol,
        int $directoryMode,
        string $expectedMessage
    ): void {
        [$tester, $collection] = $this->createTesterWithCollection();
        $reportFile = sprintf('%s://directory/report.json', $protocol);

        $this->expectFindCalls($collection, [], []);
        $this->expectNoBulkWrite($collection);
        $this->registerReportStream($protocol, $directoryMode);

        try {
            $this->assertSame(Command::FAILURE, $tester->execute(['--report-file' => $reportFile]));
            $this->assertStringContainsString($expectedMessage, $tester->getDisplay());
            $this->assertStringContainsString($reportFile, $tester->getDisplay());
        } finally {
            $this->unregisterReportStream($protocol);
        }
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

    /** @return list<TestDocument> */
    private function dryRunCandidates(): array
    {
        return [
            [
                'email' => mb_strtoupper($this->faker->safeEmail(), 'UTF-8'),
                '_id' => $this->faker->uuid(),
            ],
        ];
    }

    /** @return list<TestDocument> */
    private function duplicateCandidates(string $email): array
    {
        return [
            ['_id' => $this->faker->uuid(), 'email' => mb_strtoupper($email, 'UTF-8')],
            ['_id' => $this->faker->uuid(), 'email' => $email],
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

    private function assertDuplicateReportFile(
        string $reportFile,
        string $normalizedDuplicateEmail
    ): void {
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
        $this->assertSame([$normalizedDuplicateEmail], $report['decoded']['duplicates']);
        $this->assertIsString($report['decoded']['generatedAt']);
    }

    private function normalizeEmail(string $email): string
    {
        return (new EmailNormalizer())->normalize($email);
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
