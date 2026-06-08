<?php

declare(strict_types=1);

namespace App\Tests\Integration\User\Infrastructure\Repository;

/**
 * @psalm-type NEmailDocValue = object|array|string|int|float|bool|null
 * @psalm-type NEmailDoc = array<string, NEmailDocValue>|\ArrayAccess<string, NEmailDocValue>
 */

use App\Tests\Integration\User\UserIntegrationTestCase;
use App\User\Application\Service\EmailNormalizer;
use App\User\Domain\Collection\UserCollection;
use App\User\Domain\Exception\DuplicateEmailException;
use App\User\Infrastructure\Command\BackfillUserNormalizedEmailsBackfiller;
use App\User\Infrastructure\Command\BackfillUserNormalizedEmailsCommand;
use App\User\Infrastructure\Command\BackfillUserNormalizedEmailsReportWriter;
use ArrayAccess;
use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\Model\IndexInfo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Serializer\SerializerInterface;

final class MongoDBUserRepositoryNormalizedEmailIntegrationTest extends UserIntegrationTestCase
{
    private DocumentManager $documentManager;
    private SerializerInterface $serializer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->documentManager = $this->container->get(DocumentManager::class);
        $this->serializer = $this->container->get(SerializerInterface::class);
        $this->clearUserDocumentState();
    }

    public function testSavePersistsNormalizedEmailAndUniqueIndexRejectsCaseDuplicate(): void
    {
        $email = $this->createMixedCaseEmail();
        $this->createPersistedUserWithEmail($email);

        $document = $this->rawUserByEmail($email);
        $this->assertSame(
            (new EmailNormalizer())->normalize($email),
            $this->documentValue($document, 'normalizedEmail')
        );
        $this->assertNormalizedEmailUniqueIndexExists();

        $this->expectException(DuplicateEmailException::class);

        $this->createPersistedUserWithEmail(mb_strtoupper($email, 'UTF-8'));
    }

    public function testCaseInsensitiveLookupFindsLegacyDocumentWithoutNormalizedEmail(): void
    {
        $email = $this->createMixedCaseEmail();
        $user = $this->createPersistedUserWithEmail($email);
        $this->unsetNormalizedEmail($email);

        $users = $this->userRepository()->findByEmailCaseInsensitive(
            mb_strtoupper($email, 'UTF-8')
        );

        $this->assertUserIds([$user->getId()], $users);
    }

    public function testBackfillUpdatesLegacyDocumentsInMongoDb(): void
    {
        $email = $this->createMixedCaseEmail();
        $this->createPersistedUserWithEmail($email);
        $this->unsetNormalizedEmail($email);

        $tester = new CommandTester($this->backfillCommand());

        $this->assertSame(Command::SUCCESS, $tester->execute([]));
        $this->assertStringContainsString(
            'Backfilled normalized emails for 1 of 1 matched users.',
            $tester->getDisplay()
        );

        $document = $this->rawUserByEmail($email);
        $this->assertSame(
            (new EmailNormalizer())->normalize($email),
            $this->documentValue($document, 'normalizedEmail')
        );
    }

    public function testBackfillDryRunReportsWithoutMutatingMongoDb(): void
    {
        $email = $this->createMixedCaseEmail();
        $this->createPersistedUserWithEmail($email);
        $this->unsetNormalizedEmail($email);

        $tester = new CommandTester($this->backfillCommand());

        $this->assertSame(Command::SUCCESS, $tester->execute(['--dry-run' => true]));
        $this->assertStringContainsString(
            'Dry run completed: 1 matched users would be backfilled; 0 users modified.',
            $tester->getDisplay()
        );

        $document = $this->rawUserByEmail($email);
        $this->assertNull($this->documentValue($document, 'normalizedEmail'));
    }

    public function testBackfillAbortsWhenLegacyDocumentsNormalizeToDuplicateEmail(): void
    {
        $email = $this->createMixedCaseEmail();
        $duplicateEmail = mb_strtoupper($email, 'UTF-8');

        $this->insertLegacyUserDocumentWithEmail($email);
        $this->insertLegacyUserDocumentWithEmail($duplicateEmail);

        $tester = new CommandTester($this->backfillCommand());

        $this->assertSame(Command::FAILURE, $tester->execute([]));
        $this->assertStringContainsString(
            (new EmailNormalizer())->normalize($email),
            $tester->getDisplay()
        );

        $this->assertNull($this->documentValue($this->rawUserByEmail($email), 'normalizedEmail'));
        $this->assertNull(
            $this->documentValue($this->rawUserByEmail($duplicateEmail), 'normalizedEmail')
        );
    }

    private function unsetNormalizedEmail(string $email): void
    {
        $this->usersDocumentCollection()->updateOne(
            ['email' => $email],
            ['$unset' => ['normalizedEmail' => true]]
        );
        $this->clearUserDocumentState();
    }

    /**
     * @param list<string> $expectedUserIds
     */
    private function assertUserIds(array $expectedUserIds, UserCollection $users): void
    {
        $actualUserIds = [];

        foreach ($users as $user) {
            $actualUserIds[] = $user->getId();
        }

        $this->assertSame($expectedUserIds, $actualUserIds);
    }

    private function assertNormalizedEmailUniqueIndexExists(): void
    {
        foreach ($this->usersDocumentCollection()->listIndexes() as $index) {
            if ($this->isNormalizedEmailUniqueIndex($index)) {
                $this->assertTrue($index->offsetExists('partialFilterExpression'));

                return;
            }
        }

        $this->fail('The users collection is missing the normalizedEmail unique partial index.');
    }

    private function isNormalizedEmailUniqueIndex(IndexInfo $index): bool
    {
        return array_key_exists('normalizedEmail', $index->getKey())
            && $index->isUnique();
    }

    /** @return NEmailDoc */
    private function rawUserByEmail(string $email): array|ArrayAccess
    {
        $document = $this->usersDocumentCollection()->findOne(['email' => $email]);
        $this->assertTrue(is_array($document) || $document instanceof ArrayAccess);

        return $document;
    }

    /** @param NEmailDoc $document */
    private function documentValue(
        array|ArrayAccess $document,
        string $field
    ): object|array|string|int|float|bool|null {
        if (is_array($document)) {
            return $document[$field] ?? null;
        }

        return $document->offsetExists($field) ? $document->offsetGet($field) : null;
    }

    private function backfillCommand(): BackfillUserNormalizedEmailsCommand
    {
        return new BackfillUserNormalizedEmailsCommand(
            new BackfillUserNormalizedEmailsBackfiller(
                $this->documentManager,
                new EmailNormalizer()
            ),
            new BackfillUserNormalizedEmailsReportWriter($this->serializer)
        );
    }
}
