<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Command;

use App\User\Application\DTO\PasskeyConfiguration;
use App\User\Domain\Entity\PasskeyChallenge;
use App\User\Domain\Entity\PasskeyCredential;

use function array_key_exists;
use function array_values;

use ArrayAccess;
use Doctrine\ODM\MongoDB\DocumentManager;

use function is_array;
use function is_int;
use function is_object;
use function is_string;
use function iterator_to_array;
use function sprintf;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:passkey:assert-production-readiness',
    description: self::COMMAND_DESCRIPTION
)]
final class AssertPasskeyProductionReadinessCommand extends Command
{
    private const COMMAND_DESCRIPTION = 'Assert passkey configuration and MongoDB indexes.';
    private const REQUIRED_CREDENTIAL_INDEXES = [
        'unique credential_id index' => [
            'key' => ['credential_id' => 1],
            'unique' => true,
        ],
        'user_id lookup index' => [
            'key' => ['user_id' => 1],
            'unique' => false,
        ],
    ];
    private const REQUIRED_CHALLENGE_INDEXES = [
        'purpose and user_id lookup index' => [
            'key' => ['purpose' => 1, 'user_id' => 1],
            'unique' => false,
        ],
        'expires_at TTL index' => [
            'key' => ['expires_at' => 1],
            'unique' => false,
            'expireAfterSeconds' => 0,
        ],
    ];

    public function __construct(
        private readonly DocumentManager $documentManager,
        private readonly PasskeyConfiguration $configuration,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $io = new SymfonyStyle($input, $output);
        $failures = $this->collectFailures();

        if ($failures !== []) {
            $io->error('Passkey production readiness check failed.');
            foreach ($failures as $failure) {
                $io->writeln(sprintf(' - %s', $failure));
            }

            return Command::FAILURE;
        }

        $io->success('Passkey production readiness verified.');

        return Command::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function collectFailures(): array
    {
        $this->configuration->getAllowedOrigins();

        return [
            ...$this->missingIndexes(
                PasskeyCredential::class,
                'passkey_credentials',
                self::REQUIRED_CREDENTIAL_INDEXES
            ),
            ...$this->missingIndexes(
                PasskeyChallenge::class,
                'passkey_challenges',
                self::REQUIRED_CHALLENGE_INDEXES
            ),
        ];
    }

    /**
     * @param class-string $documentClass
     * @param array<string, array{key: array<string, int>, unique: bool, expireAfterSeconds?: int}> $requiredIndexes
     *
     * @return list<string>
     */
    private function missingIndexes(
        string $documentClass,
        string $collectionName,
        array $requiredIndexes,
    ): array {
        $indexes = $this->indexesFor($documentClass);
        $missing = [];

        foreach ($requiredIndexes as $label => $requiredIndex) {
            if ($this->hasIndex($indexes, $requiredIndex)) {
                continue;
            }

            $missing[] = sprintf('Missing %s on %s.', $label, $collectionName);
        }

        return $missing;
    }

    /**
     * @param class-string $documentClass
     *
     * @return list<array<string, string|int|bool|array<string, int>>>
     */
    private function indexesFor(string $documentClass): array
    {
        $indexes = [];
        foreach (array_values(iterator_to_array(
            $this->documentManager
                ->getDocumentCollection($documentClass)
                ->listIndexes()
        )) as $index) {
            if (!is_object($index) || !$index instanceof ArrayAccess) {
                continue;
            }

            $indexInfo = $this->indexInfoFor($index);
            if ($indexInfo !== null) {
                $indexes[] = $indexInfo;
            }
        }

        return $indexes;
    }

    /**
     * @param ArrayAccess<string, scalar|array<array-key, scalar>|null> $index
     *
     * @return array<string, string|int|bool|array<string, int>>|null
     */
    private function indexInfoFor(ArrayAccess $index): ?array
    {
        $key = $this->indexKeyFor($index);
        if ($key === null) {
            return null;
        }

        $indexInfo = ['key' => $key];
        $this->addBooleanIndexOption($indexInfo, $index, 'unique');
        $this->addBooleanIndexOption($indexInfo, $index, 'sparse');
        $this->addBooleanIndexOption($indexInfo, $index, 'hidden');
        $this->addIntegerIndexOption($indexInfo, $index, 'expireAfterSeconds');
        $this->addPresenceIndexOption($indexInfo, $index, 'partialFilterExpression');
        $this->addPresenceIndexOption($indexInfo, $index, 'collation');

        return $indexInfo;
    }

    /**
     * @param array<string, string|int|bool|array<string, int>> $indexInfo
     * @param ArrayAccess<string, scalar|array<array-key, scalar>|null> $index
     */
    private function addBooleanIndexOption(
        array &$indexInfo,
        ArrayAccess $index,
        string $optionName
    ): void {
        if ($index->offsetExists($optionName)) {
            $indexInfo[$optionName] = (bool) $index->offsetGet($optionName);
        }
    }

    /**
     * @param array<string, string|int|bool|array<string, int>> $indexInfo
     * @param ArrayAccess<string, scalar|array<array-key, scalar>|null> $index
     */
    private function addIntegerIndexOption(
        array &$indexInfo,
        ArrayAccess $index,
        string $optionName
    ): void {
        if ($index->offsetExists($optionName)) {
            $indexInfo[$optionName] = (int) $index->offsetGet($optionName);
        }
    }

    /**
     * @param array<string, string|int|bool|array<string, int>> $indexInfo
     * @param ArrayAccess<string, scalar|array<array-key, scalar>|null> $index
     */
    private function addPresenceIndexOption(
        array &$indexInfo,
        ArrayAccess $index,
        string $optionName
    ): void {
        if ($index->offsetExists($optionName)) {
            $indexInfo[$optionName] = true;
        }
    }

    /**
     * @param ArrayAccess<string, scalar|array<array-key, scalar>|null> $index
     *
     * @return array<string, int>|null
     */
    private function indexKeyFor(ArrayAccess $index): ?array
    {
        if (!$index->offsetExists('key')) {
            return null;
        }

        $key = $index->offsetGet('key');
        if (!is_array($key)) {
            return null;
        }

        return $this->normalizeIndexKey($key);
    }

    /**
     * @param array<array-key, scalar|null> $key
     *
     * @return array<string, int>|null
     */
    private function normalizeIndexKey(array $key): ?array
    {
        $normalizedKey = [];
        foreach ($key as $fieldName => $direction) {
            if (!is_string($fieldName) || !is_int($direction)) {
                return null;
            }

            $normalizedKey[$fieldName] = $direction;
        }

        return $normalizedKey;
    }

    /**
     * @param list<array<string, string|int|bool|array<string, int>>> $indexes
     * @param array{key: array<string, int>, unique: bool, expireAfterSeconds?: int} $requiredIndex
     */
    private function hasIndex(array $indexes, array $requiredIndex): bool
    {
        foreach ($indexes as $index) {
            if (($index['key'] ?? null) !== $requiredIndex['key']) {
                continue;
            }

            if ((bool) ($index['unique'] ?? false) !== $requiredIndex['unique']) {
                continue;
            }

            if (!$this->ttlMatches($index, $requiredIndex)) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * @param array{key: array<string, int>, unique: bool, expireAfterSeconds?: int} $requiredIndex
     * @param array<string, string|int|bool|array<string, int>> $index
     */
    private function ttlMatches(array $index, array $requiredIndex): bool
    {
        if ($this->hasDisqualifyingOptions($index)) {
            return false;
        }

        if (!isset($requiredIndex['expireAfterSeconds'])) {
            return !array_key_exists('expireAfterSeconds', $index);
        }

        return array_key_exists('expireAfterSeconds', $index)
            && (int) $index['expireAfterSeconds'] === $requiredIndex['expireAfterSeconds'];
    }

    /**
     * @param array<string, string|int|bool|array<string, int>> $index
     */
    private function hasDisqualifyingOptions(array $index): bool
    {
        return (bool) ($index['sparse'] ?? false)
            || (bool) ($index['hidden'] ?? false)
            || array_key_exists('partialFilterExpression', $index)
            || array_key_exists('collation', $index);
    }
}
