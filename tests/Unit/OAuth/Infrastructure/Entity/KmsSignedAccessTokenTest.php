<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Infrastructure\Entity;

use App\OAuth\Infrastructure\Entity\KmsSignedAccessToken;
use App\Shared\Application\Provider\CurrentTimestampProviderInterface;
use App\Shared\Infrastructure\Factory\KmsJwtFactory;
use App\Tests\Unit\UnitTestCase;
use DateTimeImmutable;
use League\OAuth2\Server\CryptKeyInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;

final class KmsSignedAccessTokenTest extends UnitTestCase
{
    private const NOW = 1_700_000_000;

    public function testSignsLeagueAccessTokenClaimsThroughKms(): void
    {
        $jwtFactory = $this->createMock(KmsJwtFactory::class);
        $token = $this->token($jwtFactory);
        $token->setUserIdentifier('user-1');
        $token->addScope($this->scope('email'));
        $token->addScope($this->scope('profile'));
        $jwtFactory->expects($this->once())
            ->method('create')
            ->with($this->identicalTo([
                'aud' => 'client-1',
                'jti' => 'token-1',
                'iat' => self::NOW,
                'nbf' => self::NOW,
                'exp' => self::NOW + 3600,
                'sub' => 'user-1',
                'scopes' => ['email', 'profile'],
            ]))
            ->willReturn('kms.signed.jwt');

        self::assertSame('kms.signed.jwt', $token->toString());
    }

    public function testClientCredentialsTokenUsesTheClientAsSubject(): void
    {
        $jwtFactory = $this->createMock(KmsJwtFactory::class);
        $jwtFactory->expects($this->once())
            ->method('create')
            ->with($this->callback(
                static fn (array $claims): bool => $claims['sub'] === 'client-1'
                    && $claims['scopes'] === []
            ))
            ->willReturn('kms.signed.jwt');

        self::assertSame('kms.signed.jwt', $this->token($jwtFactory)->toString());
    }

    public function testIgnoresTheLeaguePrivateKeyBecauseKmsSigns(): void
    {
        $cryptKey = $this->createMock(CryptKeyInterface::class);
        $cryptKey->expects($this->never())->method($this->anything());
        $jwtFactory = $this->createMock(KmsJwtFactory::class);
        $jwtFactory->method('create')->willReturn('kms.signed.jwt');
        $token = $this->token($jwtFactory);

        $token->setPrivateKey($cryptKey);

        self::assertSame('kms.signed.jwt', $token->toString());
    }

    private function token(KmsJwtFactory $jwtFactory): KmsSignedAccessToken
    {
        $clock = $this->createMock(CurrentTimestampProviderInterface::class);
        $clock->method('currentTimestamp')->willReturn(self::NOW);
        $client = $this->createMock(ClientEntityInterface::class);
        $client->method('getIdentifier')->willReturn('client-1');

        $token = new KmsSignedAccessToken($jwtFactory, $clock);
        $token->setIdentifier('token-1');
        $token->setClient($client);
        $token->setExpiryDateTime(new DateTimeImmutable('@' . (self::NOW + 3600)));

        return $token;
    }

    private function scope(string $identifier): ScopeEntityInterface
    {
        $scope = $this->createMock(ScopeEntityInterface::class);
        $scope->method('getIdentifier')->willReturn($identifier);

        return $scope;
    }
}
