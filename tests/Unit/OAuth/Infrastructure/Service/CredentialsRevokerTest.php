<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Infrastructure\Service;

use App\OAuth\Infrastructure\Service\CredentialsRevoker;
use App\Tests\Unit\OAuth\Infrastructure\Manager\BuilderMockFactoryTrait;
use App\Tests\Unit\UnitTestCase;
use DateTimeImmutable;
use Doctrine\ODM\MongoDB\DocumentManager;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\AccessToken;
use League\Bundle\OAuth2ServerBundle\Model\AuthorizationCode;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\Model\RefreshToken;
use League\Bundle\OAuth2ServerBundle\ValueObject\Scope;
use Symfony\Component\Security\Core\User\UserInterface;

final class CredentialsRevokerTest extends UnitTestCase
{
    use BuilderMockFactoryTrait;

    public function testRevokeCredentialsForUserUpdatesAllTokens(): void
    {
        $tokenA = $this->makeAccessToken('token_a');
        $tokenB = $this->makeAccessToken('token_b');

        $accessUpdateCaptures = [];
        $authUpdateCaptures = [];
        $accessSelectCaptures = [];
        $refreshUpdateCaptures = [];
        $accessUpdate = $this->makeBuilder(null, $accessUpdateCaptures);
        $authUpdate = $this->makeBuilder(null, $authUpdateCaptures);
        $accessSelect = $this->makeBuilder([$tokenA, $tokenB], $accessSelectCaptures);
        $refreshUpdate = $this->makeBuilder(null, $refreshUpdateCaptures);
        $builders = [$accessUpdate, $authUpdate, $accessSelect, $refreshUpdate];
        $calls = [];

        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->expects($this->exactly(4))
            ->method('createQueryBuilder')
            ->willReturnCallback(
                static function (?string $documentName = null) use (&$builders, &$calls): \Doctrine\ODM\MongoDB\Query\Builder|null {
                    $calls[] = $documentName;

                    return array_shift($builders);
                }
            );
        $documentManager->expects($this->once())->method('flush');

        $clientManager = $this->createMock(ClientManagerInterface::class);

        $revoker = new CredentialsRevoker($documentManager, $clientManager);
        $revoker->revokeCredentialsForUser($this->makeUser());

        $this->assertSame(
            [AccessToken::class, AuthorizationCode::class, AccessToken::class, RefreshToken::class],
            $calls
        );
        $this->assertSame(['token_a', 'token_b'], $refreshUpdateCaptures['in']['accessToken']);
        $this->assertSame(true, $refreshUpdateCaptures['set']['revoked']);
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
                static function () use (&$builders): \Doctrine\ODM\MongoDB\Query\Builder|null {
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
        $tokenA = $this->makeAccessToken('token_a', $client);

        $accessUpdateCaptures = [];
        $authUpdateCaptures = [];
        $accessSelectCaptures = [];
        $refreshUpdateCaptures = [];
        $accessUpdate = $this->makeBuilder(null, $accessUpdateCaptures);
        $authUpdate = $this->makeBuilder(null, $authUpdateCaptures);
        $accessSelect = $this->makeBuilder([$tokenA], $accessSelectCaptures);
        $refreshUpdate = $this->makeBuilder(null, $refreshUpdateCaptures);
        $builders = [$accessUpdate, $authUpdate, $accessSelect, $refreshUpdate];

        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->expects($this->exactly(4))
            ->method('createQueryBuilder')
            ->willReturnCallback(
                static function () use (&$builders): \Doctrine\ODM\MongoDB\Query\Builder|null {
                    return array_shift($builders);
                }
            );
        $documentManager->expects($this->once())->method('flush');

        $clientManager = $this->createMock(ClientManagerInterface::class);
        $clientManager->expects($this->once())
            ->method('find')
            ->with($client->getIdentifier())
            ->willReturn($client);

        $revoker = new CredentialsRevoker($documentManager, $clientManager);
        $revoker->revokeCredentialsForClient($client);

        $this->assertSame($client, $accessUpdateCaptures['references']['client']);
        $this->assertSame($client, $authUpdateCaptures['references']['client']);
        $this->assertSame(['token_a'], $refreshUpdateCaptures['in']['accessToken']);
    }

    private function makeClient(): Client
    {
        return new Client(
            $this->faker->company(),
            $this->faker->lexify('client_????????'),
            $this->faker->optional()->sha1()
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
