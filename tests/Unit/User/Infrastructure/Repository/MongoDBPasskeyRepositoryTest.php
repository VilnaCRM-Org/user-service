<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Repository;

use App\User\Domain\Entity\PasskeyChallenge;
use App\User\Domain\Entity\PasskeyCredential;
use App\User\Domain\ValueObject\PasskeyChallengeContext;
use App\User\Infrastructure\Repository\MongoDBPasskeyChallengeRepository;
use App\User\Infrastructure\Repository\MongoDBPasskeyCredentialRepository;
use App\User\Infrastructure\Repository\MongoDBWriteResultCounter;
use DateTimeImmutable;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Query\Builder;
use Doctrine\ODM\MongoDB\Query\Query;
use PHPUnit\Framework\MockObject\MockObject;

final class MongoDBPasskeyRepositoryTest extends MongoDBRepositoryTestCase
{
    private DocumentManager&MockObject $documentManager;
    private ManagerRegistry&MockObject $registry;
    private MongoDBWriteResultCounter $writeResultCounter;
    private string $challenge;
    private string $challengeId;
    private string $credentialId;
    private string $credentialLabel;
    private string $credentialRecord;
    private string $email;
    private string $passkeyId;
    private string $userId;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->documentManager = $this->createMock(DocumentManager::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->writeResultCounter = new MongoDBWriteResultCounter();
        $this->challenge = $this->faker->sha256();
        $this->challengeId = $this->faker->uuid();
        $this->credentialId = $this->faker->uuid();
        $this->credentialLabel = $this->faker->words(2, true);
        $this->credentialRecord = json_encode(['record' => true], JSON_THROW_ON_ERROR);
        $this->email = $this->faker->safeEmail();
        $this->passkeyId = $this->faker->uuid();
        $this->userId = $this->faker->uuid();
    }

    public function testChallengeRepositorySavesFindsAndDeletesChallenges(): void
    {
        $challenge = $this->createChallenge();
        $this->expectRegistryFor(PasskeyChallenge::class);
        $repository = $this->createRepositoryMock(
            MongoDBPasskeyChallengeRepository::class,
            [$this->documentManager, $this->registry, $this->writeResultCounter],
            ['find']
        );

        $this->documentManager->expects($this->once())->method('persist')->with($challenge);
        $this->documentManager->expects($this->exactly(2))->method('flush');
        $repository->expects($this->once())
            ->method('find')
            ->with($this->challengeId)
            ->willReturn($challenge);
        $this->documentManager->expects($this->once())->method('remove')->with($challenge);

        $repository->save($challenge);
        self::assertSame($challenge, $repository->findById($this->challengeId));
        $repository->delete($challenge);
    }

    public function testChallengeRepositoryClaimsActiveChallengeAtomically(): void
    {
        $challenge = $this->createChallenge();
        $claimedAt = new DateTimeImmutable();
        $this->expectRegistryFor(PasskeyChallenge::class);
        $repository = $this->createChallengeRepositoryWithClaimResult(1, $challenge);

        $this->documentManager->expects($this->once())
            ->method('clear')
            ->with(PasskeyChallenge::class);

        self::assertSame(
            $challenge,
            $repository->claimActive(
                $this->challengeId,
                PasskeyChallenge::PURPOSE_AUTHENTICATION,
                $claimedAt
            )
        );
    }

    public function testChallengeRepositoryReturnsNullWhenClaimDoesNotUpdate(): void
    {
        $this->expectRegistryFor(PasskeyChallenge::class);
        $repository = $this->createChallengeRepositoryWithClaimResult(0, null);

        $this->documentManager->expects($this->never())->method('clear');

        self::assertNull($repository->claimActive(
            $this->challengeId,
            PasskeyChallenge::PURPOSE_AUTHENTICATION,
            new DateTimeImmutable()
        ));
    }

    public function testCredentialRepositorySavesFindsListsAndChecksCredential(): void
    {
        $credential = $this->createCredential();
        $this->expectRegistryFor(PasskeyCredential::class);
        $queryBuilder = $this->createMock(Builder::class);
        $query = $this->createMock(Query::class);
        $repository = $this->createRepositoryMock(
            MongoDBPasskeyCredentialRepository::class,
            [$this->documentManager, $this->registry],
            ['findOneBy', 'findBy', 'createQueryBuilder']
        );
        $repository->method('createQueryBuilder')->willReturn($queryBuilder);

        $this->expectCredentialSaved($credential);
        $this->expectCredentialLookup($repository, $credential);
        $this->expectUserCredentialList($repository, $credential);
        $this->expectCredentialExistsQuery($queryBuilder, $query);

        $repository->save($credential);
        self::assertSame($credential, $repository->findByCredentialId($this->credentialId));
        self::assertSame([$credential], $repository->findByUserId($this->userId));
        self::assertTrue($repository->existsByCredentialId($this->credentialId));
    }

    private function expectRegistryFor(string $className): void
    {
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with($className)
            ->willReturn($this->documentManager);
    }

    private function expectCredentialSaved(PasskeyCredential $credential): void
    {
        $this->documentManager->expects($this->once())->method('persist')->with($credential);
        $this->documentManager->expects($this->once())->method('flush');
    }

    private function createChallengeRepositoryWithClaimResult(
        int $updateResult,
        ?PasskeyChallenge $challenge
    ): MongoDBPasskeyChallengeRepository {
        $queryBuilder = $this->createMock(Builder::class);
        $query = $this->createMock(Query::class);
        $repository = $this->createRepositoryMockWithQueryBuilder(
            MongoDBPasskeyChallengeRepository::class,
            [$this->documentManager, $this->registry, $this->writeResultCounter],
            $queryBuilder,
            ['find']
        );

        $this->stubChallengeClaimQuery($queryBuilder, $query, $updateResult);
        if ($challenge instanceof PasskeyChallenge) {
            $repository->expects($this->once())
                ->method('find')
                ->with($this->challengeId)
                ->willReturn($challenge);
        } else {
            $repository->expects($this->never())->method('find');
        }

        self::assertInstanceOf(MongoDBPasskeyChallengeRepository::class, $repository);

        return $repository;
    }

    private function stubChallengeClaimQuery(
        Builder $queryBuilder,
        Query $query,
        int $updateResult
    ): void {
        $queryBuilder->expects($this->once())->method('updateOne')->willReturnSelf();
        $queryBuilder->method('field')->willReturnSelf();
        $queryBuilder->method('equals')->willReturnSelf();
        $queryBuilder->method('gte')->willReturnSelf();
        $queryBuilder->method('set')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);
        $query->method('execute')->willReturn($updateResult);
    }

    private function expectCredentialLookup(
        MongoDBPasskeyCredentialRepository&MockObject $repository,
        PasskeyCredential $credential
    ): void {
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(['credentialId' => $this->credentialId])
            ->willReturn($credential);
    }

    private function expectUserCredentialList(
        MongoDBPasskeyCredentialRepository&MockObject $repository,
        PasskeyCredential $credential
    ): void {
        $repository->expects($this->once())
            ->method('findBy')
            ->with(['userId' => $this->userId])
            ->willReturn([1 => $credential]);
    }

    private function expectCredentialExistsQuery(Builder $queryBuilder, Query $query): void
    {
        $queryBuilder->expects($this->once())
            ->method('field')
            ->with('credentialId')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('equals')
            ->with($this->credentialId)
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('count')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects($this->once())->method('execute')->willReturn(1);
    }

    private function createChallenge(): PasskeyChallenge
    {
        $createdAt = new DateTimeImmutable();

        return new PasskeyChallenge(
            $this->challengeId,
            PasskeyChallenge::PURPOSE_AUTHENTICATION,
            $this->challenge,
            $this->optionsJson(),
            $createdAt,
            $createdAt->modify('+5 minutes'),
            new PasskeyChallengeContext($this->email, userId: $this->userId)
        );
    }

    private function createCredential(): PasskeyCredential
    {
        return new PasskeyCredential(
            $this->passkeyId,
            $this->userId,
            $this->credentialId,
            $this->credentialRecord,
            $this->credentialLabel,
            new DateTimeImmutable()
        );
    }

    private function optionsJson(): string
    {
        return json_encode(['challenge' => $this->challenge], JSON_THROW_ON_ERROR);
    }
}
