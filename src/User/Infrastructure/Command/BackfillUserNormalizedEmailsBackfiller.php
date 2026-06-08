<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Command;

use App\User\Application\Service\EmailNormalizer;
use App\User\Domain\Entity\User;
use ArrayAccess;
use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\Collection;

/**
 * @psalm-type BackfillId = object|int|string|null
 * @psalm-type DocumentValue = object|string|int|float|bool|null
 * @psalm-type BackfillCandidate = array{id: BackfillId, email: string, normalizedEmail: string}
 * @psalm-type BackfillResult = array{matched: int, modified: int, duplicates: list<string>, dryRun: bool}
 */
final class BackfillUserNormalizedEmailsBackfiller
{
    private const BATCH_SIZE = 100;

    public function __construct(
        private readonly DocumentManager $documentManager,
        private readonly EmailNormalizer $emailNormalizer,
    ) {
    }

    /** @return BackfillResult */
    public function backfill(bool $dryRun): array
    {
        $candidates = $this->collectCandidates();
        $duplicates = $this->findDuplicateNormalizedEmails($candidates);

        if ($duplicates !== []) {
            return [
                'matched' => count($candidates),
                'modified' => 0,
                'duplicates' => $duplicates,
                'dryRun' => $dryRun,
            ];
        }

        return [
            'matched' => count($candidates),
            'modified' => $dryRun ? 0 : $this->updateCandidates($candidates),
            'duplicates' => [],
            'dryRun' => $dryRun,
        ];
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

    /** @param array<string, DocumentValue>|ArrayAccess<string, DocumentValue> $document */
    private function documentStringValue(array|ArrayAccess $document, string $field): ?string
    {
        $value = $this->documentValue($document, $field);

        return is_string($value) ? $value : null;
    }

    /** @param array<string, DocumentValue>|ArrayAccess<string, DocumentValue> $document */
    private function documentIdValue(array|ArrayAccess $document): object|int|string|null
    {
        $value = $this->documentValue($document, '_id');

        if (is_object($value) || is_int($value) || is_string($value)) {
            return $value;
        }

        return null;
    }

    /** @param array<string, DocumentValue>|ArrayAccess<string, DocumentValue> $document */
    private function documentValue(array|ArrayAccess $document, string $field): mixed
    {
        if (is_array($document)) {
            return $document[$field] ?? null;
        }

        return $document->offsetExists($field) ? $document->offsetGet($field) : null;
    }

    private function usersCollection(): Collection
    {
        return $this->documentManager->getDocumentCollection(User::class);
    }
}
