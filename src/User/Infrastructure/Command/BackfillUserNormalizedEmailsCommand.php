<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Command;

use function is_string;

use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @psalm-type BackfillResult = array{matched: int, modified: int, duplicates: list<string>, dryRun: bool}
 */
#[AsCommand(
    name: 'app:backfill-user-normalized-emails',
    description: self::COMMAND_DESCRIPTION
)]
final class BackfillUserNormalizedEmailsCommand extends Command
{
    private const COMMAND_DESCRIPTION = 'Backfill canonical email values for existing users.';
    private const DUPLICATE_PREVIEW_LIMIT = 5;
    private const OPTION_DRY_RUN = 'dry-run';
    private const OPTION_REPORT_FILE = 'report-file';

    public function __construct(
        private readonly BackfillUserNormalizedEmailsBackfiller $backfiller,
        private readonly BackfillUserNormalizedEmailsReportWriter $reportWriter,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->addOption(
                self::OPTION_DRY_RUN,
                null,
                InputOption::VALUE_NONE,
                'Scan and report without updating user documents.'
            )
            ->addOption(
                self::OPTION_REPORT_FILE,
                null,
                InputOption::VALUE_REQUIRED,
                'Write a JSON report with matched, modified, duplicate, and dry-run results.'
            );
    }

    #[\Override]
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $io = new SymfonyStyle($input, $output);
        $result = $this->backfiller->backfill(
            (bool) $input->getOption(self::OPTION_DRY_RUN)
        );
        $reportFile = $this->stringOption($input->getOption(self::OPTION_REPORT_FILE));

        if ($reportFile !== null) {
            try {
                $this->reportWriter->write($reportFile, $result);
            } catch (RuntimeException $error) {
                $io->error($error->getMessage());

                return Command::FAILURE;
            }

            $io->text(sprintf('Backfill report written to %s', $reportFile));
        }

        if ($result['duplicates'] !== []) {
            $this->writeDuplicateFailure($io, $result);

            return Command::FAILURE;
        }

        $this->writeSuccess($io, $result);

        return Command::SUCCESS;
    }

    /** @param BackfillResult $result */
    private function writeSuccess(SymfonyStyle $io, array $result): void
    {
        if ($result['dryRun']) {
            $io->success(sprintf(
                'Dry run completed: %d matched users would be backfilled; %d users modified.',
                $result['matched'],
                $result['modified']
            ));

            return;
        }

        $io->success(sprintf(
            'Backfilled normalized emails for %d of %d matched users.',
            $result['modified'],
            $result['matched']
        ));
    }

    /** @param BackfillResult $result */
    private function writeDuplicateFailure(SymfonyStyle $io, array $result): void
    {
        $io->error(sprintf(
            'Backfill aborted: scanned %d matched users, modified %d users, duplicates: %s',
            $result['matched'],
            $result['modified'],
            implode(', ', array_slice($result['duplicates'], 0, self::DUPLICATE_PREVIEW_LIMIT))
        ));
    }

    private function stringOption(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
