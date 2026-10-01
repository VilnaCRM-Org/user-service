<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Infrastructure\Repository;

use App\OAuth\Infrastructure\Entity\KmsSignedAccessToken;
use App\OAuth\Infrastructure\Repository\KmsAccessTokenRepository;
use App\Shared\Application\Provider\CurrentTimestampProviderInterface;
use App\Shared\Infrastructure\Factory\KmsJwtFactory;
use App\Tests\Unit\UnitTestCase;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;

final class KmsAccessTokenRepositoryTest extends UnitTestCase
{
    private AccessTokenRepositoryInterface&MockObject $inner;
    private KmsAccessTokenRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->inner = $this->createMock(AccessTokenRepositoryInterface::class);
        $this->repository = new KmsAccessTokenRepository(
            $this->inner,
            $this->createMock(KmsJwtFactory::class),
            $this->createMock(CurrentTimestampProviderInterface::class)
        );
    }

    public function testNewTokenIsKmsSignedAndCarriesClientUserAndScopes(): void
    {
        $client = $this->createMock(ClientEntityInterface::class);
        $scope = $this->createMock(ScopeEntityInterface::class);
        $scope->method('getIdentifier')->willReturn('email');

        $token = $this->repository->getNewToken($client, [$scope], 'user-1');

        self::assertInstanceOf(KmsSignedAccessToken::class, $token);
        self::assertSame($client, $token->getClient());
        self::assertSame('user-1', $token->getUserIdentifier());
        self::assertSame([$scope], $token->getScopes());
    }

    /**
     * @dataProvider missingUserIdentifiers
     */
    public function testNewTokenWithoutUserHasNoUserIdentifier(?string $userIdentifier): void
    {
        $token = $this->repository->getNewToken(
            $this->createMock(ClientEntityInterface::class),
            [],
            $userIdentifier
        );

        self::assertNull($token->getUserIdentifier());
    }

    /**
     * @return iterable<string, array{string|null}>
     */
    public static function missingUserIdentifiers(): iterable
    {
        yield 'null' => [null];
        yield 'empty' => [''];
    }

    public function testPersistsThroughTheDecoratedRepository(): void
    {
        $token = $this->createMock(AccessTokenEntityInterface::class);
        $this->inner->expects($this->once())->method('persistNewAccessToken')->with($token);

        $this->repository->persistNewAccessToken($token);
    }

    public function testRevokesThroughTheDecoratedRepository(): void
    {
        $this->inner->expects($this->once())->method('revokeAccessToken')->with('token-1');

        $this->repository->revokeAccessToken('token-1');
    }

    /**
     * @dataProvider revocationStates
     */
    public function testReadsRevocationFromTheDecoratedRepository(bool $revoked): void
    {
        $this->inner->method('isAccessTokenRevoked')->with('token-1')->willReturn($revoked);

        self::assertSame($revoked, $this->repository->isAccessTokenRevoked('token-1'));
    }

    /**
     * @return iterable<string, array{bool}>
     */
    public static function revocationStates(): iterable
    {
        yield 'revoked' => [true];
        yield 'active' => [false];
    }
}
