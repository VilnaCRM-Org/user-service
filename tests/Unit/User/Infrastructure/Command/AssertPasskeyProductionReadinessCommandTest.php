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
use stdClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class AssertPasskeyProductionReadinessCommandTest extends UnitTestCase
{
    public function testExecuteSucceedsWhenConfigurationAndIndexesAreReady(): void
    {
        $tester = new CommandTester($this->createCommand(
            $this->createValidConfiguration(),
            $this->requiredCredentialIndexes(),
            $this->requiredChallengeIndexes()
        ));

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertStringContainsString(
            'Passkey production readiness verified.',
            $tester->getDisplay()
        );
    }

    public function testExecuteFailsWhenCredentialUniqueIndexIsMissing(): void
    {
        $tester = new CommandTester($this->createCommand(
            $this->createValidConfiguration(),
            [
                $this->index(['credential_id' => 1]),
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

    public function testExecuteFailsWhenCredentialUserIdIndexIsMissing(): void
    {
        $tester = new CommandTester($this->createCommand(
            $this->createValidConfiguration(),
            [
                $this->uniqueIndex(['credential_id' => 1]),
            ],
            $this->requiredChallengeIndexes()
        ));

        self::assertSame(Command::FAILURE, $tester->execute([]));
        self::assertStringContainsString(
            'Missing user_id lookup index on passkey_credentials.',
            $tester->getDisplay()
        );
    }

    public function testExecuteFailsWhenChallengeLookupIndexIsMissing(): void
    {
        $tester = new CommandTester($this->createCommand(
            $this->createValidConfiguration(),
            $this->requiredCredentialIndexes(),
            [
                $this->ttlIndex(['expires_at' => 1], 0),
            ]
        ));

        self::assertSame(Command::FAILURE, $tester->execute([]));
        self::assertStringContainsString(
            'Missing purpose and user_id lookup index on passkey_challenges.',
            $tester->getDisplay()
        );
    }

    public function testExecuteFailsWhenChallengeTtlIndexIsMissing(): void
    {
        $tester = new CommandTester($this->createCommand(
            $this->createValidConfiguration(),
            $this->requiredCredentialIndexes(),
            [
                $this->index(['purpose' => 1, 'user_id' => 1]),
                $this->ttlIndex(['expires_at' => 1], 60),
            ]
        ));

        self::assertSame(Command::FAILURE, $tester->execute([]));
        self::assertStringContainsString(
            'Missing expires_at TTL index on passkey_challenges.',
            $tester->getDisplay()
        );
    }

    public function testExecuteFailsWhenCredentialUniqueIndexIsPartial(): void
    {
        $tester = new CommandTester($this->createCommand(
            $this->createValidConfiguration(),
            [
                $this->uniqueIndex(
                    ['credential_id' => 1],
                    ['partialFilterExpression' => ['credential_id' => ['$exists' => true]]]
                ),
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

    public function testExecuteFailsWhenCredentialUniqueIndexIsSparse(): void
    {
        $tester = new CommandTester($this->createCommand(
            $this->createValidConfiguration(),
            [
                $this->uniqueIndex(['credential_id' => 1], ['sparse' => true]),
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

    public function testExecuteFailsWhenCredentialUniqueIndexHasCustomCollation(): void
    {
        $tester = new CommandTester($this->createCommand(
            $this->createValidConfiguration(),
            [
                $this->uniqueIndex(
                    ['credential_id' => 1],
                    ['collation' => ['locale' => 'en', 'strength' => 2]]
                ),
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

    public function testExecuteFailsWhenChallengeTtlIndexIsHidden(): void
    {
        $tester = new CommandTester($this->createCommand(
            $this->createValidConfiguration(),
            $this->requiredCredentialIndexes(),
            [
                $this->index(['purpose' => 1, 'user_id' => 1]),
                $this->ttlIndex(['expires_at' => 1], 0, ['hidden' => true]),
            ]
        ));

        self::assertSame(Command::FAILURE, $tester->execute([]));
        self::assertStringContainsString(
            'Missing expires_at TTL index on passkey_challenges.',
            $tester->getDisplay()
        );
    }

    public function testExecuteIgnoresUnexpectedIndexEntries(): void
    {
        $tester = new CommandTester($this->createCommand(
            $this->createValidConfiguration(),
            [
                new stdClass(),
                ...$this->requiredCredentialIndexes(),
            ],
            [
                new stdClass(),
                ...$this->requiredChallengeIndexes(),
            ]
        ));

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertStringContainsString(
            'Passkey production readiness verified.',
            $tester->getDisplay()
        );
    }

    public function testExecuteIgnoresMalformedArrayAccessIndexEntries(): void
    {
        $tester = new CommandTester($this->createCommand(
            $this->createValidConfiguration(),
            [
                new ArrayObject(['name' => 'credential_id']),
                new ArrayObject(['key' => 'credential_id']),
                new ArrayObject(['key' => [0 => 1]]),
                new ArrayObject(['key' => ['credential_id' => 'ascending']]),
                ...$this->requiredCredentialIndexes(),
            ],
            $this->requiredChallengeIndexes()
        ));

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertStringContainsString(
            'Passkey production readiness verified.',
            $tester->getDisplay()
        );
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
        $info = [
            'v' => 2,
            'name' => implode('_', array_keys($key)),
            'key' => $key,
        ];

        foreach ($options as $name => $value) {
            $info[$name] = $value;
        }

        return new IndexInfo($info);
    }
}
