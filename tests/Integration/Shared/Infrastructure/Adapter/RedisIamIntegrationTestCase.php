<?php

declare(strict_types=1);

namespace App\Tests\Integration\Shared\Infrastructure\Adapter;

use App\Shared\Infrastructure\Adapter\RedisIamAuthenticator;
use App\Shared\Infrastructure\Factory\PhpRedisClientFactory;
use App\Shared\Infrastructure\Factory\RedisIamAuthTokenFactory;
use App\Shared\Infrastructure\Factory\RedisIamConnectionFactory;
use App\Shared\Infrastructure\Observability\Factory\AuthFailureMetricFactory;
use App\Tests\Integration\Shared\SharedIntegrationTestCase;
use App\Tests\Unit\Shared\Infrastructure\Observability\BusinessMetricsEmitterSpy;
use Aws\Credentials\Credentials;
use GuzzleHttp\Promise\Create;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Symfony\Component\HttpClient\Psr18Client;

/**
 * Runs against the local TLS + ACL Valkey 7.2 service "valkey-iam". As AD-02
 * requires, its "default" user is off; the tests set up users through the
 * separate local "admin" ACL user. The app user gets the reviewed AD-02
 * access string and, as its only password, the SigV4 token that ElastiCache
 * would accept at the current (simulated) time.
 */
abstract class RedisIamIntegrationTestCase extends SharedIntegrationTestCase
{
    protected const AD02_ACCESS_STRING = ['on', '~*', '+@all', '-@dangerous'];
    private const ADMIN_USER = 'admin';

    protected \Redis $admin;
    protected MutableCurrentTimestampProvider $clock;
    protected BusinessMetricsEmitterSpy $metricsEmitter;
    protected TestHandler $logs;
    protected Credentials $credentials;
    protected string $userId;
    protected string $replicationGroupId;
    protected string $region;
    protected string $dsn;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->dsn = (string) getenv('VALKEY_IAM_TEST_DSN');
        $this->admin = $this->unauthenticatedConnection();
        $this->admin->auth([self::ADMIN_USER, $this->adminPassword()]);
        $this->clock = new MutableCurrentTimestampProvider(time());
        $this->metricsEmitter = new BusinessMetricsEmitterSpy();
        $this->logs = new TestHandler();
        $this->credentials = $this->newCredentials();
        $this->userId = strtolower($this->faker->lexify('user-service-it-????????'));
        $this->replicationGroupId = strtolower($this->faker->lexify('user-service-it-rg-????'));
        $this->region = $this->faker->randomElement(['eu-central-1', 'us-east-1']);
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->admin->rawCommand('ACL', 'DELUSER', $this->userId);
        $this->admin->close();

        parent::tearDown();
    }

    protected function connectionFactory(?string $userId = null): RedisIamConnectionFactory
    {
        $connectingUserId = $userId ?? $this->userId;

        return new RedisIamConnectionFactory(
            new PhpRedisClientFactory(),
            new RedisIamAuthenticator(
                $this->tokenFactory($connectingUserId),
                $this->metricsEmitter,
                new AuthFailureMetricFactory(),
                new Logger('redis-iam-test', [$this->logs]),
                $connectingUserId
            ),
            $this->clock,
            $this->tlsStreamOptions()
        );
    }

    protected function currentToken(?string $userId = null): string
    {
        return $this->tokenFactory($userId ?? $this->userId)->create()->value();
    }

    protected function acceptOnlyTokens(string ...$tokens): void
    {
        $passwords = array_map(static fn (string $token): string => '>' . $token, $tokens);
        $this->admin->rawCommand(
            'ACL',
            'SETUSER',
            $this->userId,
            'reset',
            ...self::AD02_ACCESS_STRING,
            ...$passwords
        );
    }

    protected function unauthenticatedConnection(): \Redis
    {
        $connection = new \Redis();
        $connection->connect('tls://' . $this->host(), $this->port(), 2.0, null, 0, 2.0, [
            'stream' => $this->tlsStreamOptions(),
        ]);

        return $connection;
    }

    protected function clientId(\Redis $connection): int
    {
        return (int) $connection->rawCommand('CLIENT', 'ID');
    }

    protected function newCredentials(?int $expiresIn = null): Credentials
    {
        return new Credentials(
            $this->faker->bothify('ASIA############'),
            $this->faker->sha256(),
            $this->faker->sha256(),
            $expiresIn === null ? null : $this->clock->currentTimestamp() + $expiresIn
        );
    }

    /**
     * @return array<string, bool|string>
     */
    protected function tlsStreamOptions(): array
    {
        return [
            'verify_peer' => true,
            'verify_peer_name' => true,
            'cafile' => (string) getenv('VALKEY_IAM_TEST_CA_FILE'),
        ];
    }

    private function tokenFactory(string $userId): RedisIamAuthTokenFactory
    {
        return new RedisIamAuthTokenFactory(
            new Psr18Client(),
            fn () => Create::promiseFor($this->credentials),
            $this->clock,
            $this->replicationGroupId,
            $userId,
            $this->region
        );
    }

    private function adminPassword(): string
    {
        return trim((string) file_get_contents(
            (string) getenv('VALKEY_IAM_TEST_ADMIN_PASSWORD_FILE')
        ));
    }

    private function host(): string
    {
        return (string) parse_url($this->dsn, PHP_URL_HOST);
    }

    private function port(): int
    {
        return (int) parse_url($this->dsn, PHP_URL_PORT);
    }
}
