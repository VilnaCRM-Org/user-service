<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Command;

use DateTimeImmutable;

use function dirname;
use function file_put_contents;
use function is_dir;
use function is_writable;

use RuntimeException;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @psalm-type BackfillResult = array{matched: int, modified: int, duplicates: list<string>, dryRun: bool}
 */
final class BackfillUserNormalizedEmailsReportWriter
{
    public function __construct(private readonly SerializerInterface $serializer)
    {
    }

    /** @param BackfillResult $result */
    public function write(string $reportFile, array $result): void
    {
        $this->assertReportDirectoryWritable($reportFile);
        $json = $this->serializer->encode(
            $this->payload($result),
            JsonEncoder::FORMAT,
            ['json_encode_options' => JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR]
        );

        if (file_put_contents($reportFile, $json . PHP_EOL) === false) {
            throw new RuntimeException(sprintf(
                'Backfill report could not be written to %s.',
                $reportFile
            ));
        }
    }

    private function assertReportDirectoryWritable(string $reportFile): void
    {
        $directory = dirname($reportFile);

        if (! is_dir($directory)) {
            throw new RuntimeException(sprintf(
                'Backfill report directory does not exist for %s.',
                $reportFile
            ));
        }

        if (! is_writable($directory)) {
            throw new RuntimeException(sprintf(
                'Backfill report directory is not writable for %s.',
                $reportFile
            ));
        }
    }

    /**
     * @param BackfillResult $result
     *
     * @return array{
     *     generatedAt: string,
     *     status: string,
     *     matched: int,
     *     modified: int,
     *     dryRun: bool,
     *     duplicates: list<string>
     * }
     */
    private function payload(array $result): array
    {
        return [
            'generatedAt' => (new DateTimeImmutable())->format(DATE_ATOM),
            'status' => $result['duplicates'] === [] ? 'success' : 'duplicate_failure',
            'matched' => $result['matched'],
            'modified' => $result['modified'],
            'dryRun' => $result['dryRun'],
            'duplicates' => $result['duplicates'],
        ];
    }
}
