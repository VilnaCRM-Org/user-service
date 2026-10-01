<?php

declare(strict_types=1);

namespace App\Tests\Unit\Config;

use App\Shared\Infrastructure\Adapter\RedisIamAuthenticator;
use App\Shared\Infrastructure\Factory\RedisConnectionFactory;
use App\Shared\Infrastructure\Factory\RedisIamAuthTokenFactory;
use App\Shared\Infrastructure\Factory\RedisIamConnectionFactory;
use App\Shared\Infrastructure\Factory\StandardRedisConnectionFactory;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Guards the IAM-mode Redis wiring in config/services.yaml (S5.13, D-1).
 */
final class RedisIamConnectionConfigTest extends UnitTestCase
{
    private const CONNECTION_FACTORY = ['@' . RedisConnectionFactory::class, 'create'];

    private const REDIS_CONNECTIONS = [
        'app.redis_connection' => '%env(resolve:REDIS_URL)%',
        'oauth.redis_connection' => '%env(resolve:REDIS_URL)%',
        'app.account_lockout_redis_connection' => '%env(resolve:REDIS_LOCKOUT_URL)%',
    ];

    public function testIamConnectionsVerifyTheServerCertificate(): void
    {
        $options = $this->service(RedisIamConnectionFactory::class)['arguments']['$tlsStreamOptions'];

        self::assertSame(['verify_peer' => true, 'verify_peer_name' => true], $options);
        self::assertArrayNotHasKey('allow_self_signed', $options);
    }

    public function testTokensAreSignedWithTheDefaultAwsCredentialChain(): void
    {
        $provider = $this->service('app.redis_iam_aws_credential_provider');
        $arguments = $this->service(RedisIamAuthTokenFactory::class)['arguments'];

        self::assertSame('Closure', $provider['class']);
        self::assertSame(['Aws\Credentials\CredentialProvider', 'defaultProvider'], $provider['factory']);
        self::assertArrayNotHasKey('arguments', $provider);
        self::assertSame('@app.redis_iam_aws_credential_provider', $arguments['$credentialProvider']);
        self::assertSame('@psr18.http_client', $arguments['$requestFactory']);
    }

    public function testIamSettingsAreBoundToEnvironmentVariables(): void
    {
        $tokenFactory = $this->service(RedisIamAuthTokenFactory::class)['arguments'];
        $authenticator = $this->service(RedisIamAuthenticator::class)['arguments'];
        $selector = $this->service(RedisConnectionFactory::class)['arguments'];

        self::assertSame('%env(REDIS_IAM_USER_ID)%', $selector['$iamUserId']);
        self::assertSame('%env(REDIS_IAM_USER_ID)%', $authenticator['$userId']);
        self::assertSame('@' . RedisIamAuthTokenFactory::class, $authenticator['$tokenFactory']);
        self::assertSame('%env(REDIS_IAM_USER_ID)%', $tokenFactory['$userId']);
        self::assertSame(
            '%env(REDIS_REPLICATION_GROUP_ID)%',
            $tokenFactory['$replicationGroupId']
        );
        self::assertSame('%env(AWS_REGION)%', $tokenFactory['$region']);
        self::assertSame('', $this->config()['parameters']['env(AWS_REGION)']);
    }

    public function testConnectionFactorySelectsBetweenStandardAndIamFactories(): void
    {
        $arguments = $this->service(RedisConnectionFactory::class)['arguments'];

        self::assertSame(
            '@' . StandardRedisConnectionFactory::class,
            $arguments['$standardConnectionFactory']
        );
        self::assertSame('@' . RedisIamConnectionFactory::class, $arguments['$iamConnectionFactory']);
    }

    public function testEveryRedisConnectionIsCreatedByTheConnectionFactory(): void
    {
        foreach (self::REDIS_CONNECTIONS as $serviceId => $dsn) {
            $service = $this->service($serviceId);

            self::assertSame('\Redis', $service['class'], $serviceId);
            self::assertSame(self::CONNECTION_FACTORY, $service['factory'], $serviceId);
            self::assertSame([$dsn], $service['arguments'], $serviceId);
        }
    }

    public function testNoEnvironmentOverridesTheRedisWiring(): void
    {
        $serviceIds = [
            ...array_keys(self::REDIS_CONNECTIONS),
            RedisConnectionFactory::class,
            RedisIamConnectionFactory::class,
            RedisIamAuthenticator::class,
            RedisIamAuthTokenFactory::class,
            'app.redis_iam_aws_credential_provider',
        ];

        foreach (['dev', 'test', 'load_test', 'schemathesis', 'prod'] as $environment) {
            $services = $this->config()['when@' . $environment]['services'] ?? [];

            self::assertSame([], array_intersect($serviceIds, array_keys($services)), $environment);
        }
    }

    public function testCachePoolsUseTheAppRedisConnection(): void
    {
        $cache = Yaml::parseFile(dirname(__DIR__, 3) . '/config/packages/cache.yaml')['framework']['cache'];

        self::assertSame('app.redis_connection', $cache['default_redis_provider']);
        self::assertSame('app.redis_connection', $cache['pools']['app']['provider']);
        self::assertSame('app.redis_connection', $cache['pools']['cache.user']['provider']);
    }

    /**
     * @return array<string, array<string, array<string, bool>|list<string>|string>|string>
     */
    private function service(string $serviceId): array
    {
        $services = $this->config()['services'];
        self::assertArrayHasKey($serviceId, $services);

        return $services[$serviceId];
    }

    /**
     * @return array<string, array<string, array<string, array<string, array<string, bool>|list<string>|string>|string>|string>>
     */
    private function config(): array
    {
        return Yaml::parseFile(
            dirname(__DIR__, 3) . '/config/services.yaml',
            Yaml::PARSE_CUSTOM_TAGS
        );
    }
}
