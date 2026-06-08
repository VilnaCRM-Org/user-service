<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Command;

use App\User\Application\Service\EmailNormalizer;
use App\User\Domain\Entity\User;
use ArrayAccess;
use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\Collection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @psalm-type BackfillId = object|int|string|null
 * @psalm-type DocumentValue = object|string|int|float|bool|null
 * @psalm-type BackfillCandidate = array{id: BackfillId, email: string, normalizedEmail: string}
 * @psalm-type BackfillResult = array{matched: int, modified: int, duplicates: list<string>}
 */
#[AsCommand(
    name: 'app:backfill-user-normalized-emails',
    description: self::COMMAND_DESCRIPTION
)]
final class BackfillUserNormalizedEmailsCommand extends Command
{
    private const COMMAND_DESCRIPTION = 'Backfill canonical email values for existing users.';
    private const BATCH_SIZE = 100;
    private const DUPLICATE_PREVIEW_LIMIT = 5;

    public function __construct(
        private readonly DocumentManager $documentManager,
        private readonly EmailNormalizer $emailNormalizer,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $io = new SymfonyStyle($input, $output);
        $result = $this->backfillNormalizedEmails();

        if ($result['duplicates'] !== []) {
            $this->writeDuplicateFailure($io, $result);

            return Command::FAILURE;
        }

        $this->writeSuccess($io, $result);

        return Command::SUCCESS;
    }

    /** @return BackfillResult */
    private function backfillNormalizedEmails(): array
    {
        $candidates = $this->collectCandidates();
        $duplicates = $this->findDuplicateNormalizedEmails($candidates);

        if ($duplicates !== []) {
            return [
                'matched' => count($candidates),
                'modified' => 0,
                'duplicates' => $duplicates,
            ];
        }

        return [
            'matched' => count($candidates),
            'modified' => $this->updateCandidates($candidates),
            'duplicates' => [],
        ];
    }

    /** @param BackfillResult $result */
    private function writeSuccess(SymfonyStyle $io, array $result): void
    {
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
            'batchSize' => self::BATCH_SIZE,
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
            'batchSize' => self::BATCH_SIZE,
        ];
    }

    /** @return list<BackfillCandidate> */
    private function collectCandidates(): array
    {
        $candidates = [];
        $cursor = $this->usersCollection()
            ->find($this->backfillFilter(), $this->candidateFindOptions());

        foreach ($cursor as $document) {
            if (! is_array($document) && !$document instanceof ArrayAccess) {
                continue;
            }

            $email = $this->documentStringValue($document, 'email');

            if ($email === null) {
                continue;
            }

            $candidates[] = [
                'id' => $this->documentIdValue($document),
                'email' => $email,
                'normalizedEmail' => $this->emailNormalizer->normalize($email),
            ];
        }

        return $candidates;
    }

    /**
     * @param list<BackfillCandidate> $candidates
     *
     * @return list<string>
     */
    private function findDuplicateNormalizedEmails(array $candidates): array
    {
        $seen = [];
        $duplicateEmails = [];
        $duplicateIndex = [];

        foreach ($candidates as $candidate) {
            $normalizedEmail = $candidate['normalizedEmail'];

            if (isset($seen[$normalizedEmail])) {
                $this->appendUniqueNormalizedEmail(
                    $duplicateEmails,
                    $duplicateIndex,
                    $normalizedEmail
                );
                continue;
            }

            $seen[$normalizedEmail] = $normalizedEmail;
        }

        foreach ($this->existingNormalizedEmails(array_values($seen)) as $normalizedEmail) {
            $this->appendUniqueNormalizedEmail($duplicateEmails, $duplicateIndex, $normalizedEmail);
        }

        return $duplicateEmails;
    }

    /**
     * @param list<string> $normalizedEmails
     *
     * @return list<string>
     */
    private function existingNormalizedEmails(array $normalizedEmails): array
    {
        if ($normalizedEmails === []) {
            return [];
        }

        $existingNormalizedEmails = [];
        $existingNormalizedEmailIndex = [];
        $cursor = $this->usersCollection()
            ->find($this->existingFilter($normalizedEmails), $this->existingFindOptions());

        foreach ($cursor as $document) {
            $normalizedEmail = $this->normalizedEmailFromDocument($document);

            if ($normalizedEmail === null) {
                continue;
            }

            $this->appendUniqueNormalizedEmail(
                $existingNormalizedEmails,
                $existingNormalizedEmailIndex,
                $normalizedEmail
            );
        }

        return $existingNormalizedEmails;
    }

    /**
     * @param list<string> $normalizedEmails
     * @param array<string, string> $normalizedEmailIndex
     */
    private function appendUniqueNormalizedEmail(
        array &$normalizedEmails,
        array &$normalizedEmailIndex,
        string $normalizedEmail
    ): void {
        if (isset($normalizedEmailIndex[$normalizedEmail])) {
            return;
        }

        $normalizedEmailIndex[$normalizedEmail] = $normalizedEmail;
        $normalizedEmails[] = $normalizedEmail;
    }

    /** @param array<string, DocumentValue>|object|null $document */
    private function normalizedEmailFromDocument(object|array|null $document): ?string
    {
        if (! is_array($document) && !$document instanceof ArrayAccess) {
            return null;
        }

        return $this->documentStringValue($document, 'normalizedEmail');
    }

    /**
     * @param list<string> $normalizedEmails
     *
     * @return array{normalizedEmail: array{'$in': list<string>}}
     */
    private function existingFilter(array $normalizedEmails): array
    {
        return [
            'normalizedEmail' => [
                '$in' => $normalizedEmails,
            ],
        ];
    }

    /** @param list<BackfillCandidate> $candidates */
    private function updateCandidates(array $candidates): int
    {
        $modified = 0;

        foreach (array_chunk($candidates, self::BATCH_SIZE) as $batch) {
            $result = $this->usersCollection()->bulkWrite($this->updateOperations($batch));
            $modified += $result->getModifiedCount();
        }

        return $modified;
    }

    /**
     * @param list<BackfillCandidate> $candidates
     *
     * @return list<array{
     *     updateOne: array{
     *         0: array{
     *             _id: BackfillId,
     *             '$or': list<array{normalizedEmail: array{'$exists': false}|string}>
     *         },
     *         1: array{'$set': array{normalizedEmail: string}}
     *     }
     * }>
     */
    private function updateOperations(array $candidates): array
    {
        $operations = [];

        foreach ($candidates as $candidate) {
            $operations[] = [
                'updateOne' => [
                    $this->updateFilter($candidate['id']),
                    ['$set' => ['normalizedEmail' => $candidate['normalizedEmail']]],
                ],
            ];
        }

        return $operations;
    }

    /**
     * @return array{
     *     _id: BackfillId,
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

    /**
     * @param array<string, DocumentValue>|ArrayAccess<array-key, DocumentValue> $document
     */
    private function documentStringValue(array|ArrayAccess $document, string $field): ?string
    {
        $value = $this->documentValue($document, $field);

        return is_string($value) ? $value : null;
    }

    /**
     * @param array<string, DocumentValue>|ArrayAccess<array-key, DocumentValue> $document
     *
     * @return BackfillId
     */
    private function documentIdValue(array|ArrayAccess $document): object|int|string|null
    {
        $value = $this->documentValue($document, '_id');

        if (is_object($value) || is_int($value) || is_string($value)) {
            return $value;
        }

        return null;
    }

    /**
     * @param array<string, DocumentValue>|ArrayAccess<array-key, DocumentValue> $document
     *
     * @return DocumentValue
     */
    private function documentValue(
        array|ArrayAccess $document,
        string $field
    ): object|string|int|float|bool|null {
        if (is_array($document)) {
            return $this->arrayDocumentValue($document, $field);
        }

        return $this->arrayAccessDocumentValue($document, $field);
    }

    /**
     * @param array<string, DocumentValue> $document
     *
     * @return DocumentValue
     */
    private function arrayDocumentValue(
        array $document,
        string $field
    ): object|string|int|float|bool|null {
        return $document[$field] ?? null;
    }

    /**
     * @param ArrayAccess<array-key, DocumentValue> $document
     *
     * @return DocumentValue
     */
    private function arrayAccessDocumentValue(
        ArrayAccess $document,
        string $field
    ): object|string|int|float|bool|null {
        return $document->offsetExists($field) ? $document->offsetGet($field) : null;
    }

    private function usersCollection(): Collection
    {
        return $this->documentManager->getDocumentCollection(User::class);
    }
}
