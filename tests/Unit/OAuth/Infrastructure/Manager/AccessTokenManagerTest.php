<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Infrastructure\Manager;

use App\OAuth\Infrastructure\Manager\AccessTokenManager;
use App\Tests\Unit\OAuth\Infrastructure\OAuthInfrastructureTestCase;
use DateTimeImmutable;
use Doctrine\ODM\MongoDB\DocumentManager;
use League\Bundle\OAuth2ServerBundle\Model\AccessToken;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\Model\RefreshToken;
use League\Bundle\OAuth2ServerBundle\ValueObject\Scope;

final class AccessTokenManagerTest extends OAuthInfrastructureTestCase
{
    public function testFindReturnsNullWhenPersistenceDisabled(): void
    {
        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->expects($this->never())->method('find');

        $manager = new AccessTokenManager($documentManager, false);

        $this->assertNull($manager->find($this->faker->uuid()));
    }

    public function testSaveDoesNothingWhenPersistenceDisabled(): void
    {
        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->expects($this->never())->method('persist');
        $documentManager->expects($this->never())->method('flush');

        $manager = new AccessTokenManager($documentManager, false);

        $manager->save($this->makeAccessToken());
    }

    public function testClearExpiredReturnsZeroWhenPersistenceDisabled(): void
    {
        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->expects($this->never())->method('createQueryBuilder');

        $manager = new AccessTokenManager($documentManager, false);

        $this->assertSame(0, $manager->clearExpired());
    }

    public function testFindReturnsAccessTokenWhenPersistenceEnabled(): void
    {
        $identifier = $this->faker->lexify('token_????????');
        $accessToken = $this->makeAccessTokenWithIdentifier($identifier);

        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->expects($this->once())
            ->method('find')
            ->with(AccessToken::class, $identifier)
            ->willReturn($accessToken);

        $manager = new AccessTokenManager($documentManager, true);

        $this->assertSame($accessToken, $manager->find($identifier));
    }

    public function testSavePersistsAccessTokenWhenPersistenceEnabled(): void
    {
        $accessToken = $this->makeAccessToken();

        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->expects($this->once())->method('persist')->with($accessToken);
        $documentManager->expects($this->once())->method('flush');

        $manager = new AccessTokenManager($documentManager, true);

        $manager->save($accessToken);
    }

    public function testClearExpiredReturnsZeroWhenNoExpiredTokens(): void
    {
        $expiredCaptures = [];
        $expiredBuilder = $this->makeBuilder([], $expiredCaptures);

        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->expects($this->once())
            ->method('createQueryBuilder')
            ->with(AccessToken::class)
            ->willReturn($expiredBuilder);

        $manager = new AccessTokenManager($documentManager, true);

        $this->assertSame(0, $manager->clearExpired());
    }

    public function testClearExpiredRemovesExpiredTokensAndUnlinksRefreshTokens(): void
    {
        [$tokenAId, $tokenBId] = [$this->faker->uuid(), $this->faker->uuid()];
        $tokenA = $this->makeAccessTokenWithIdentifier($tokenAId);
        $tokenB = $this->makeAccessTokenWithIdentifier($tokenBId);
        $captures = $this->createCaptureArrays();
        $calls = [];
        $documentManager = $this->createDocumentManagerForClearExpired(
            $tokenA,
            $tokenB,
            $captures,
            $calls
        );

        $manager = new AccessTokenManager($documentManager, true);

        $this->assertSame(2, $manager->clearExpired());
        $this->assertSame(
            [AccessToken::class, RefreshToken::class, AccessToken::class],
            $calls
        );
        $this->assertSame([$tokenAId, $tokenBId], $captures['refresh']['in']['accessToken']);
        $this->assertSame(null, $captures['refresh']['set']['accessToken']);
        $this->assertSame([$tokenAId, $tokenBId], $captures['remove']['in']['identifier']);
    }

    private function makeAccessToken(): AccessToken
    {
        $client = new Client(
            $this->faker->company(),
            $this->faker->lexify('client_????????'),
            $this->faker->sha1()
        );

        return new AccessToken(
            $this->faker->lexify('token_????????'),
            new DateTimeImmutable('+1 hour'),
            $client,
            $this->faker->optional()->userName(),
            [new Scope($this->faker->lexify('scope_????'))]
        );
    }

    private function makeAccessTokenWithIdentifier(string $identifier): AccessToken
    {
        $client = new Client(
            $this->faker->company(),
            $this->faker->lexify('client_????????'),
            $this->faker->sha1()
        );

        return new AccessToken(
            $identifier,
            new DateTimeImmutable('+1 hour'),
            $client,
            $this->faker->optional()->userName(),
            [new Scope($this->faker->lexify('scope_????'))]
        );
    }

    /**
     * @return array[]
     *
     * @psalm-return array{expired: array<never, never>, refresh: array<never, never>, remove: array<never, never>}
     */
    private function createCaptureArrays(): array
    {
        return ['expired' => [], 'refresh' => [], 'remove' => []];
    }

    /**
     * @param array<string, array<string, array|bool|float|int|object|string|null>> $captures
     * @param list<string|null> $calls
     */
    private function createDocumentManagerForClearExpired(
        AccessToken $tokenA,
        AccessToken $tokenB,
        array &$captures,
        array &$calls
    ): \PHPUnit\Framework\MockObject\MockObject&DocumentManager {
        $builders = [
            $this->makeBuilder([$tokenA, $tokenB], $captures['expired']),
            $this->makeBuilder(null, $captures['refresh']),
            $this->makeBuilder(null, $captures['remove']),
        ];
        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->expects($this->exactly(3))
            ->method('createQueryBuilder')
            ->willReturnCallback(
                static function (?string $documentName = null) use (&$builders, &$calls) {
                    $calls[] = $documentName;
                    return array_shift($builders);
                }
            );

        return $documentManager;
    }
}
