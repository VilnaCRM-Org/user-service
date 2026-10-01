<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Factory;

use App\Shared\Application\Provider\CurrentTimestampProviderInterface;
use App\Shared\Infrastructure\Factory\RedisIamAuthTokenFactory;
use App\Tests\Unit\UnitTestCase;
use Aws\Credentials\Credentials;
use GuzzleHttp\Promise\Create;
use Symfony\Component\Cache\Exception\InvalidArgumentException;
use Symfony\Component\HttpClient\Psr18Client;

final class RedisIamAuthTokenFactoryTest extends UnitTestCase
{
    private SigV4PresignedTokenVerifier $verifier;
    private string $replicationGroupId;
    private string $userId;
    private string $region;
    private int $issuedAt;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->verifier = new SigV4PresignedTokenVerifier();
        $this->replicationGroupId = strtolower($this->faker->lexify('rg-????????'));
        $this->userId = strtolower($this->faker->lexify('app-user-??????'));
        $this->region = $this->faker->randomElement(['eu-central-1', 'us-east-1', 'eu-west-1']);
        $this->issuedAt = $this->faker->numberBetween(1_700_000_000, 1_900_000_000);
    }

    public function testCreatesPresignedConnectTokenForReplicationGroupAndUser(): void
    {
        $token = $this->factory([$this->sessionCredentials()])->create();

        [$host, $query] = $this->verifier->split($token);
        $parameters = $this->verifier->parameters($query);
        self::assertStringStartsWith($this->replicationGroupId . '/?', $token);
        self::assertSame($this->replicationGroupId, $host);
        self::assertSame('connect', $parameters['Action']);
        self::assertSame($this->userId, $parameters['User']);
        self::assertSame('AWS4-HMAC-SHA256', $parameters['X-Amz-Algorithm']);
        self::assertSame('900', $parameters['X-Amz-Expires']);
        self::assertSame('host', $parameters['X-Amz-SignedHeaders']);
    }

    public function testSignsWithSessionCredentialsForRegionAndIssueTime(): void
    {
        $credentials = $this->sessionCredentials();

        $token = $this->factory([$credentials])->create();

        $parameters = $this->verifier->parameters($this->verifier->split($token)[1]);
        self::assertSame(gmdate('Ymd\THis\Z', $this->issuedAt), $parameters['X-Amz-Date']);
        self::assertSame($credentials->getSecurityToken(), $parameters['X-Amz-Security-Token']);
        self::assertSame(
            sprintf(
                '%s/%s/%s/elasticache/aws4_request',
                $credentials->getAccessKeyId(),
                gmdate('Ymd', $this->issuedAt),
                $this->region
            ),
            $parameters['X-Amz-Credential']
        );
    }

    public function testSignatureMatchesIndependentSigV4Computation(): void
    {
        $credentials = $this->sessionCredentials();

        $token = $this->factory([$credentials])->create();

        $parameters = $this->verifier->parameters($this->verifier->split($token)[1]);
        self::assertSame(
            $this->verifier->expectedSignature($token, $credentials->getSecretKey(), $this->region),
            $parameters['X-Amz-Signature']
        );
    }

    public function testOmitsSecurityTokenForLongTermCredentials(): void
    {
        $credentials = new Credentials(
            $this->faker->bothify('AKIA############'),
            $this->faker->sha256()
        );

        $token = $this->factory([$credentials])->create();

        self::assertArrayNotHasKey(
            'X-Amz-Security-Token',
            $this->verifier->parameters($this->verifier->split($token)[1])
        );
    }

    public function testLowerCasesTheReplicationGroupId(): void
    {
        $upperCaseGroupId = strtoupper($this->replicationGroupId);
        $factory = new RedisIamAuthTokenFactory(
            new Psr18Client(),
            $this->credentialProvider([$this->sessionCredentials()]),
            $this->timestampProvider(),
            $upperCaseGroupId,
            $this->userId,
            $this->region
        );

        self::assertStringStartsWith($this->replicationGroupId . '/?', $factory->create());
    }

    public function testRegeneratesTokenFromRotatedCredentials(): void
    {
        $first = $this->sessionCredentials();
        $rotated = $this->sessionCredentials();
        $factory = $this->factory([$first, $rotated]);

        $firstToken = $factory->create();
        $rotatedToken = $factory->create();

        self::assertNotSame($firstToken, $rotatedToken);
        self::assertStringContainsString(
            'X-Amz-Credential=' . $rotated->getAccessKeyId() . '%2F',
            $rotatedToken
        );
        self::assertSame(
            $this->verifier->expectedSignature(
                $rotatedToken,
                $rotated->getSecretKey(),
                $this->region
            ),
            $this->verifier->parameters($this->verifier->split($rotatedToken)[1])['X-Amz-Signature']
        );
    }

    public function testRejectsMissingReplicationGroupId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Redis IAM authentication requires REDIS_REPLICATION_GROUP_ID.'
        );

        $this->configuredFactory('', $this->userId, $this->region)->create();
    }

    public function testRejectsMissingUserId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Redis IAM authentication requires REDIS_IAM_USER_ID.');

        $this->configuredFactory($this->replicationGroupId, '', $this->region)->create();
    }

    public function testRejectsMissingRegion(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Redis IAM authentication requires AWS_REGION.');

        $this->configuredFactory($this->replicationGroupId, $this->userId, '')->create();
    }

    public function testDoesNotResolveCredentialsWhenConfigurationIsMissing(): void
    {
        $resolved = false;
        $factory = new RedisIamAuthTokenFactory(
            new Psr18Client(),
            static function () use (&$resolved): never {
                $resolved = true;
                throw new \LogicException('credentials must not be resolved');
            },
            $this->timestampProvider(),
            '',
            $this->userId,
            $this->region
        );

        $this->assertConfigurationIsRejected($factory);
        self::assertFalse($resolved);
    }

    private function assertConfigurationIsRejected(RedisIamAuthTokenFactory $factory): void
    {
        try {
            $factory->create();
            self::fail('Missing configuration was accepted.');
        } catch (InvalidArgumentException $exception) {
            self::assertStringContainsString(
                'REDIS_REPLICATION_GROUP_ID',
                $exception->getMessage()
            );
        }
    }

    private function configuredFactory(
        string $replicationGroupId,
        string $userId,
        string $region
    ): RedisIamAuthTokenFactory {
        return new RedisIamAuthTokenFactory(
            new Psr18Client(),
            $this->credentialProvider([$this->sessionCredentials()]),
            $this->timestampProvider(),
            $replicationGroupId,
            $userId,
            $region
        );
    }

    /**
     * @param list<Credentials> $credentials
     */
    private function factory(array $credentials): RedisIamAuthTokenFactory
    {
        return new RedisIamAuthTokenFactory(
            new Psr18Client(),
            $this->credentialProvider($credentials),
            $this->timestampProvider(),
            $this->replicationGroupId,
            $this->userId,
            $this->region
        );
    }

    /**
     * @param list<Credentials> $credentials
     */
    private function credentialProvider(array $credentials): \Closure
    {
        return static function () use (&$credentials) {
            return Create::promiseFor(array_shift($credentials));
        };
    }

    private function timestampProvider(): CurrentTimestampProviderInterface
    {
        $timestampProvider = $this->createMock(CurrentTimestampProviderInterface::class);
        $timestampProvider->method('currentTimestamp')->willReturn($this->issuedAt);

        return $timestampProvider;
    }

    private function sessionCredentials(): Credentials
    {
        return new Credentials(
            $this->faker->bothify('ASIA############'),
            $this->faker->sha256(),
            $this->faker->sha256() . $this->faker->sha1()
        );
    }
}
