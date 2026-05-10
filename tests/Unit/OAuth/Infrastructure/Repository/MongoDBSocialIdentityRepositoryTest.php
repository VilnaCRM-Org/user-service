<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Infrastructure\Repository;

use App\OAuth\Domain\Entity\SocialIdentity;
use App\OAuth\Domain\ValueObject\OAuthProvider;
use App\OAuth\Infrastructure\Repository\MongoDBSocialIdentityRepository;
use App\Tests\Unit\UnitTestCase;
use DateTimeImmutable;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\ODM\MongoDB\DocumentManager;
use PHPUnit\Framework\MockObject\MockObject;

final class MongoDBSocialIdentityRepositoryTest extends UnitTestCase
{
    private DocumentManager|MockObject $documentManager;
    private ManagerRegistry|MockObject $registry;
    private MongoDBSocialIdentityRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->documentManager = $this->createMock(DocumentManager::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->registry
            ->expects($this->atLeastOnce())
            ->method('getManagerForClass')
            ->with(SocialIdentity::class)
            ->willReturn($this->documentManager);

        $this->repository = new MongoDBSocialIdentityRepository(
            $this->documentManager,
            $this->registry,
        );
    }

    public function testSavePersistsAndFlushes(): void
    {
        $identity = $this->createSocialIdentity();

        $this->documentManager->expects($this->once())
            ->method('persist')
            ->with($identity);
        $this->documentManager->expects($this->once())
            ->method('flush');

        $this->repository->save($identity);
    }

    public function testFindByProviderAndProviderIdReturnsIdentity(): void
    {
        $provider = new OAuthProvider('github');
        $providerId = $this->faker->numerify('######');
        $expectedIdentity = $this->createSocialIdentity();
        $repository = $this->createRepositoryWithFindOneBy(
            ['provider' => (string) $provider, 'providerId' => $providerId],
            $expectedIdentity,
        );

        $result = $repository->findByProviderAndProviderId(
            $provider,
            $providerId,
        );

        $this->assertSame($expectedIdentity, $result);
    }

    public function testFindByUserIdAndProviderReturnsIdentity(): void
    {
        $userId = $this->faker->uuid();
        $provider = new OAuthProvider('google');
        $expectedIdentity = $this->createSocialIdentity();
        $repository = $this->createRepositoryWithFindOneBy(
            ['userId' => $userId, 'provider' => (string) $provider],
            $expectedIdentity,
        );

        $result = $repository->findByUserIdAndProvider($userId, $provider);

        $this->assertSame($expectedIdentity, $result);
    }

    /**
     * @param array<string, string> $criteria
     */
    private function createRepositoryWithFindOneBy(
        array $criteria,
        SocialIdentity $returnValue,
    ): MongoDBSocialIdentityRepository {
        $repository = $this->getMockBuilder(
            MongoDBSocialIdentityRepository::class
        )
            ->setConstructorArgs([
                $this->documentManager,
                $this->registry,
            ])
            ->onlyMethods(['findOneBy'])
            ->getMock();

        $repository->expects($this->once())
            ->method('findOneBy')
            ->with($criteria)
            ->willReturn($returnValue);

        return $repository;
    }

    private function createSocialIdentity(): SocialIdentity
    {
        return new SocialIdentity(
            $this->faker->uuid(),
            new OAuthProvider('github'),
            $this->faker->numerify('######'),
            $this->faker->uuid(),
            new DateTimeImmutable(),
        );
    }
}
