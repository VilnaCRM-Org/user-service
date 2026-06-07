<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Command;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\PasskeyConfiguration;
use App\User\Domain\Entity\PasskeyChallenge;
use App\User\Domain\Entity\PasskeyCredential;
use App\User\Infrastructure\Command\AssertPasskeyProductionReadinessCommand;
use ArrayIterator;
use ArrayObject;
use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\Collection;
use MongoDB\Model\IndexInfo;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionMethod;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class AssertPasskeyProductionReadinessCommandMutationTest extends UnitTestCase
{
    public function testExecuteReportsEveryMissingCredentialIndex(): void
    {
        $tester = new CommandTester($this->createCommand(
            $this->createValidConfiguration(),
            [],
            $this->requiredChallengeIndexes()
        ));

        self::assertSame(Command::FAILURE, $tester->execute([]));

        $display = $tester->getDisplay();
        self::assertStringContainsString(
            'Missing unique credential_id index on passkey_credentials.',
            $display
        );
        self::assertStringContainsString(
            'Missing user_id lookup index on passkey_credentials.',
            $display
        );
    }

    public function testExecuteFailsWhenCredentialUniqueIndexHasTruthySparseOption(): void
    {
        $tester = new CommandTester($this->createCommand(
            $this->createValidConfiguration(),
            [
                new ArrayObject([
                    'key' => ['credential_id' => 1],
                    'unique' => true,
                    'sparse' => 1,
                ]),
                $this->index(['user_id' => 1]),
            ],
            $this->requiredChallengeIndexes()
        ));

        self::assertSame(Command::FAILURE, $tester->execute([]));
        self::assertStringContainsString(
            'Missing unique credential_id index on passkey_credentials.',
            $tester->getDisplay()
        );
    }

    public function testExecuteAcceptsScalarIndexOptionsAfterNormalization(): void
    {
        $tester = new CommandTester($this->createCommand(
            $this->createValidConfiguration(),
            $this->scalarCredentialIndexes(),
            $this->scalarChallengeIndexes()
        ));

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertStringContainsString(
            'Passkey production readiness verified.',
            $tester->getDisplay()
        );
    }

    public function testExecuteContinuesPastIndexesWithMatchingKeyButWrongOptions(): void
    {
        $tester = new CommandTester($this->createCommand(
            $this->createValidConfiguration(),
            [
                $this->index(['credential_id' => 1]),
                $this->uniqueIndex(['credential_id' => 1]),
                $this->index(['user_id' => 1]),
            ],
            [
                $this->index(['purpose' => 1, 'user_id' => 1]),
                $this->ttlIndex(['expires_at' => 1], 60),
                $this->ttlIndex(['expires_at' => 1], 0),
            ]
        ));

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertStringContainsString(
            'Passkey production readiness verified.',
            $tester->getDisplay()
        );
    }

    public function testIndexInfoRejectsIndexWithoutKey(): void
    {
        self::assertNull($this->indexInfoFor(new ArrayObject(['unique' => true])));
    }

    public function testNormalizeIndexKeyRejectsNonStringFieldName(): void
    {
        self::assertNull($this->normalizeIndexKey([0 => 1]));
    }

    public function testNormalizeIndexKeyRejectsNonIntegerDirection(): void
    {
        self::assertNull($this->normalizeIndexKey(['credential_id' => '1']));
    }

    /**
     * @param list<object> $credentialIndexes
     * @param list<object> $challengeIndexes
     */
    private function createCommand(
        PasskeyConfiguration $configuration,
        array $credentialIndexes,
        array $challengeIndexes
    ): AssertPasskeyProductionReadinessCommand {
        return new AssertPasskeyProductionReadinessCommand(
            $this->createDocumentManager($credentialIndexes, $challengeIndexes),
            $configuration
        );
    }

    /**
     * @param list<object> $credentialIndexes
     * @param list<object> $challengeIndexes
     */
    private function createDocumentManager(
        array $credentialIndexes,
        array $challengeIndexes
    ): DocumentManager&MockObject {
        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager
            ->method('getDocumentCollection')
            ->willReturnMap([
                [
                    PasskeyCredential::class,
                    $this->createCollection($credentialIndexes),
                ],
                [
                    PasskeyChallenge::class,
                    $this->createCollection($challengeIndexes),
                ],
            ]);

        return $documentManager;
    }

    /**
     * @param list<object> $indexes
     */
    private function createCollection(array $indexes): Collection&MockObject
    {
        $collection = $this->createMock(Collection::class);
        $collection
            ->method('listIndexes')
            ->willReturn(new ArrayIterator($indexes));

        return $collection;
    }

    private function createValidConfiguration(): PasskeyConfiguration
    {
        $rpId = $this->faker->domainName();

        return new PasskeyConfiguration(
            $rpId,
            $this->faker->company(),
            sprintf('https://app.%s', $rpId),
            $this->faker->numberBetween(60, 600),
            $this->faker->numberBetween(60, 600),
            'prod'
        );
    }

    /**
     * @param ArrayObject<string, scalar|array<array-key, scalar>|null> $index
     *
     * @return array<string, string|int|bool|array<string, int>>|null
     */
    private function indexInfoFor(ArrayObject $index): ?array
    {
        $method = new ReflectionMethod(
            AssertPasskeyProductionReadinessCommand::class,
            'indexInfoFor'
        );
        $this->makeAccessible($method);

        $result = $method->invoke($this->createReadyCommand(), $index);
        if ($result === null || is_array($result)) {
            return $result;
        }

        self::fail('Index info must be an array or null.');
    }

    /**
     * @param array<array-key, scalar|null> $key
     *
     * @return array<string, int>|null
     */
    private function normalizeIndexKey(array $key): ?array
    {
        $method = new ReflectionMethod(
            AssertPasskeyProductionReadinessCommand::class,
            'normalizeIndexKey'
        );
        $this->makeAccessible($method);

        $result = $method->invoke($this->createReadyCommand(), $key);
        if ($result === null || is_array($result)) {
            return $result;
        }

        self::fail('Normalized index key must be an array or null.');
    }

    private function createReadyCommand(): AssertPasskeyProductionReadinessCommand
    {
        return $this->createCommand(
            $this->createValidConfiguration(),
            $this->requiredCredentialIndexes(),
            $this->requiredChallengeIndexes()
        );
    }

    /**
     * @return list<IndexInfo>
     */
    private function requiredCredentialIndexes(): array
    {
        return [
            $this->uniqueIndex(['credential_id' => 1]),
            $this->index(['user_id' => 1]),
        ];
    }

    /**
     * @return list<IndexInfo>
     */
    private function requiredChallengeIndexes(): array
    {
        return [
            $this->index(['purpose' => 1, 'user_id' => 1]),
            $this->ttlIndex(['expires_at' => 1], 0),
        ];
    }

    /**
     * @return list<object>
     */
    private function scalarCredentialIndexes(): array
    {
        return [
            new ArrayObject([
                'key' => ['credential_id' => 1],
                'unique' => 1,
            ]),
            $this->index(['user_id' => 1]),
        ];
    }

    /**
     * @return list<object>
     */
    private function scalarChallengeIndexes(): array
    {
        return [
            $this->index(['purpose' => 1, 'user_id' => 1]),
            new ArrayObject([
                'key' => ['expires_at' => 1],
                'expireAfterSeconds' => '0',
            ]),
        ];
    }

    /**
     * @param array<string, int> $key
     */
    private function index(array $key): IndexInfo
    {
        return $this->indexWithOptions($key, []);
    }

    /**
     * @param array<string, int> $key
     * @param array<string, bool|int|array<string, bool|int|string|array<string, bool>>> $options
     */
    private function uniqueIndex(array $key, array $options = []): IndexInfo
    {
        return $this->indexWithOptions($key, ['unique' => true, ...$options]);
    }

    /**
     * @param array<string, int> $key
     * @param array<string, bool|int|array<string, bool|int|string|array<string, bool>>> $options
     */
    private function ttlIndex(
        array $key,
        int $expireAfterSeconds,
        array $options = []
    ): IndexInfo {
        return $this->indexWithOptions($key, [
            'expireAfterSeconds' => $expireAfterSeconds,
            ...$options,
        ]);
    }

    /**
     * @param array<string, int> $key
     * @param array<string, bool|int|array<string, bool|int|string|array<string, bool>>> $options
     */
    private function indexWithOptions(array $key, array $options): IndexInfo
    {
        return new IndexInfo([
            'v' => 2,
            'name' => implode('_', array_keys($key)),
            'key' => $key,
            ...$options,
        ]);
    }
}
