<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Infrastructure\Service;

use App\OAuth\Infrastructure\Revoker\CredentialsRevoker;
use App\Tests\Unit\OAuth\Infrastructure\OAuthInfrastructureTestCase;
use DateTimeImmutable;
use Doctrine\ODM\MongoDB\DocumentManager;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\AccessToken;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\ValueObject\Scope;
use Symfony\Component\Security\Core\User\UserInterface;

final class CredentialsRevokerTest extends OAuthInfrastructureTestCase
{
    public function testRevokeCredentialsForUserUpdatesAllTokens(): void
    {
        $tokenAId = $this->faker->lexify('token_????');
        $tokenBId = $this->faker->lexify('token_????');
        $tokenA = $this->makeAccessToken($tokenAId);
        $tokenB = $this->makeAccessToken($tokenBId);

        $captures = $this->createCaptureArrays();
        $documentManager = $this->createDocManagerForUserRevocation(
            $tokenA,
            $tokenB,
            $captures
        );
        $clientManager = $this->createMock(ClientManagerInterface::class);

        $revoker = new CredentialsRevoker($documentManager, $clientManager);
        $revoker->revokeCredentialsForUser($this->makeUser());

        $this->assertRefreshTokensCaptured($captures, [$tokenAId, $tokenBId]);
    }

    public function testRevokeCredentialsForUserSkipsRefreshTokensWhenNoAccessTokens(): void
    {
        $accessUpdateCaptures = [];
        $authUpdateCaptures = [];
        $accessSelectCaptures = [];
        $accessUpdate = $this->makeBuilder(null, $accessUpdateCaptures);
        $authUpdate = $this->makeBuilder(null, $authUpdateCaptures);
        $accessSelect = $this->makeBuilder([], $accessSelectCaptures);
        $builders = [$accessUpdate, $authUpdate, $accessSelect];

        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->expects($this->exactly(3))
            ->method('createQueryBuilder')
            ->willReturnCallback(
                static function () use (&$builders) {
                    return array_shift($builders);
                }
            );
        $documentManager->expects($this->once())->method('flush');

        $clientManager = $this->createMock(ClientManagerInterface::class);

        $revoker = new CredentialsRevoker($documentManager, $clientManager);
        $revoker->revokeCredentialsForUser($this->makeUser());

        $this->assertEmpty($accessSelectCaptures['in'] ?? []);
    }

    public function testRevokeCredentialsForClientReturnsWhenClientNotFound(): void
    {
        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->expects($this->never())->method('createQueryBuilder');
        $documentManager->expects($this->never())->method('flush');

        $clientManager = $this->createMock(ClientManagerInterface::class);
        $clientManager->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $revoker = new CredentialsRevoker($documentManager, $clientManager);
        $revoker->revokeCredentialsForClient($this->makeClient());
    }

    public function testRevokeCredentialsForClientUpdatesAllTokens(): void
    {
        $client = $this->makeClient();
        $tokenIdentifier = $this->faker->lexify('token_????');
        $tokenA = $this->makeAccessToken($tokenIdentifier, $client);

        $captures = $this->createCaptureArrays();
        $documentManager = $this->createDocManagerForClientRevocation($tokenA, $captures);
        $clientManager = $this->createClientManagerMock($client);

        $revoker = new CredentialsRevoker($documentManager, $clientManager);
        $revoker->revokeCredentialsForClient($client);

        $this->assertClientReferencesSet($captures, $client, $tokenIdentifier);
    }

    /**
     * @return array<string, array<string, array|bool|float|int|object|string|null>>
     */
    private function createCaptureArrays(): array
    {
        return [
            'accessUpdate' => [],
            'authUpdate' => [],
            'accessSelect' => [],
            'refreshUpdate' => [],
        ];
    }

    /**
     * @param array<string, array<string, array|bool|float|int|object|string|null>> $captures
     */
    private function createDocManagerForUserRevocation(
        AccessToken $tokenA,
        AccessToken $tokenB,
        array &$captures
    ): DocumentManager {
        $accessUpdate = $this->makeBuilder(null, $captures['accessUpdate']);
        $authUpdate = $this->makeBuilder(null, $captures['authUpdate']);
        $accessSelect = $this->makeBuilder(
            [$tokenA, $tokenB],
            $captures['accessSelect']
        );
        $refreshUpdate = $this->makeBuilder(null, $captures['refreshUpdate']);
        $builders = [$accessUpdate, $authUpdate, $accessSelect, $refreshUpdate];
        $calls = [];

        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->expects($this->exactly(4))
            ->method('createQueryBuilder')
            ->willReturnCallback(
                static function (?string $documentName = null) use (&$builders, &$calls) {
                    $calls[] = $documentName;

                    return array_shift($builders);
                }
            );
        $documentManager->expects($this->once())->method('flush');

        return $documentManager;
    }

    /**
     * @param array<string, array<string, array|bool|float|int|object|string|null>> $captures
     */
    private function createDocManagerForClientRevocation(
        AccessToken $tokenA,
        array &$captures
    ): DocumentManager {
        $accessUpdate = $this->makeBuilder(null, $captures['accessUpdate']);
        $authUpdate = $this->makeBuilder(null, $captures['authUpdate']);
        $accessSelect = $this->makeBuilder([$tokenA], $captures['accessSelect']);
        $refreshUpdate = $this->makeBuilder(null, $captures['refreshUpdate']);
        $builders = [$accessUpdate, $authUpdate, $accessSelect, $refreshUpdate];

        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->expects($this->exactly(4))
            ->method('createQueryBuilder')
            ->willReturnCallback(
                static function () use (&$builders) {
                    return array_shift($builders);
                }
            );
        $documentManager->expects($this->once())->method('flush');

        return $documentManager;
    }

    private function createClientManagerMock(Client $client): ClientManagerInterface
    {
        $clientManager = $this->createMock(ClientManagerInterface::class);
        $clientManager->expects($this->once())
            ->method('find')
            ->with($client->getIdentifier())
            ->willReturn($client);

        return $clientManager;
    }

    /**
     * @param array<string, array<string, array|bool|float|int|object|string|null>> $captures
     */
    /**
     * @param array<string, array<string, array|bool|float|int|object|string|null>> $captures
     * @param list<string> $expectedTokenIds
     */
    private function assertRefreshTokensCaptured(array $captures, array $expectedTokenIds): void
    {
        $this->assertSame($expectedTokenIds, $captures['refreshUpdate']['in']['accessToken']);
        $this->assertSame(true, $captures['refreshUpdate']['set']['revoked']);
    }

    /**
     * @param array<string, array<string, array|bool|float|int|object|string|null>> $captures
     */
    private function assertClientReferencesSet(
        array $captures,
        Client $client,
        string $accessTokenIdentifier
    ): void {
        $this->assertSame($client, $captures['accessUpdate']['references']['client']);
        $this->assertSame($client, $captures['authUpdate']['references']['client']);
        $this->assertSame(
            [$accessTokenIdentifier],
            $captures['refreshUpdate']['in']['accessToken']
        );
    }

    private function makeClient(): Client
    {
        return new Client(
            $this->faker->company(),
            $this->faker->lexify('client_????????'),
            $this->faker->sha1()
        );
    }

    private function makeAccessToken(string $identifier, ?Client $client = null): AccessToken
    {
        $client ??= $this->makeClient();

        return new AccessToken(
            $identifier,
            new DateTimeImmutable('+1 hour'),
            $client,
            $this->faker->optional()->userName(),
            [new Scope($this->faker->lexify('scope_????'))]
        );
    }

    private function makeUser(): UserInterface
    {
        $identifier = $this->faker->userName();

        return new class($identifier) implements UserInterface {
            public function __construct(private readonly string $identifier)
            {
            }

            #[\Override]
            public function getUserIdentifier(): string
            {
                return $this->identifier;
            }

            /**
             * @return list<string>
             */
            #[\Override]
            public function getRoles(): array
            {
                return [];
            }

            #[\Override]
            public function eraseCredentials(): void
            {
            }
        };
    }
}
