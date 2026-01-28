<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Infrastructure\Manager;

use App\OAuth\Infrastructure\Manager\RefreshTokenManager;
use App\Tests\Unit\UnitTestCase;
use DateTimeImmutable;
use Doctrine\ODM\MongoDB\DocumentManager;
use League\Bundle\OAuth2ServerBundle\Model\AccessToken;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\Model\RefreshToken;
use League\Bundle\OAuth2ServerBundle\ValueObject\Scope;

final class RefreshTokenManagerTest extends UnitTestCase
{
    use BuilderMockFactoryTrait;

    public function testFindReturnsRefreshTokenWhenFound(): void
    {
        $identifier = $this->faker->lexify('refresh_????????');
        $refreshToken = $this->makeRefreshToken($identifier);

        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->expects($this->once())
            ->method('find')
            ->with(RefreshToken::class, $identifier)
            ->willReturn($refreshToken);

        $manager = new RefreshTokenManager($documentManager);

        $this->assertSame($refreshToken, $manager->find($identifier));
    }

    public function testSavePersistsRefreshToken(): void
    {
        $refreshToken = $this->makeRefreshToken($this->faker->lexify('refresh_????????'));

        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->expects($this->once())->method('persist')->with($refreshToken);
        $documentManager->expects($this->once())->method('flush');

        $manager = new RefreshTokenManager($documentManager);

        $manager->save($refreshToken);
    }

    public function testClearExpiredRemovesRefreshTokens(): void
    {
        $captures = [];
        $result = new class(2) {
            public function __construct(private readonly int $count)
            {
            }

            public function getDeletedCount(): int
            {
                return $this->count;
            }
        };
        $builder = $this->makeBuilder($result, $captures);

        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->expects($this->once())
            ->method('createQueryBuilder')
            ->with(RefreshToken::class)
            ->willReturn($builder);

        $manager = new RefreshTokenManager($documentManager);

        $this->assertSame(2, $manager->clearExpired());
        $this->assertTrue($captures['remove'] ?? false);
        $this->assertNotEmpty($captures['lt']);
    }

    public function testClearExpiredReturnsIntDirectly(): void
    {
        $captures = [];
        $builder = $this->makeBuilder(7, $captures);

        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->expects($this->once())
            ->method('createQueryBuilder')
            ->with(RefreshToken::class)
            ->willReturn($builder);

        $manager = new RefreshTokenManager($documentManager);

        $this->assertSame(7, $manager->clearExpired());
    }

    public function testClearExpiredReturnsZeroForNullResult(): void
    {
        $captures = [];
        $builder = $this->makeBuilder(null, $captures);

        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->expects($this->once())
            ->method('createQueryBuilder')
            ->with(RefreshToken::class)
            ->willReturn($builder);

        $manager = new RefreshTokenManager($documentManager);

        $this->assertSame(0, $manager->clearExpired());
    }

    private function makeRefreshToken(string $identifier): RefreshToken
    {
        $client = new Client(
            $this->faker->company(),
            $this->faker->lexify('client_????????'),
            $this->faker->sha1()
        );

        $accessToken = new AccessToken(
            $this->faker->lexify('token_????????'),
            new DateTimeImmutable('+1 hour'),
            $client,
            $this->faker->optional()->userName(),
            [new Scope($this->faker->lexify('scope_????'))]
        );

        return new RefreshToken(
            $identifier,
            new DateTimeImmutable('+1 day'),
            $accessToken
        );
    }
}
