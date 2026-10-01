<?php

declare(strict_types=1);

namespace App\Tests\Integration\Shared\Infrastructure\Adapter;

use App\Shared\Infrastructure\Adapter\RedisIamConnection;
use App\Shared\Infrastructure\EventSubscriber\RedisIamConnectionRenewalSubscriber;
use App\Shared\Infrastructure\Factory\RedisIamConnectionFactory;

final class RedisIamConnectionRenewalTest extends RedisIamIntegrationTestCase
{
    private const ELEVEN_HOURS = 39_600;

    public function testReauthenticatesOpenConnectionWithFreshTokenAfterTenMinutes(): void
    {
        $factory = $this->connectionFactory();
        $redis = $this->openConnection($factory);
        $clientId = $this->clientId($redis);

        $this->clock->advance(RedisIamConnection::REAUTHENTICATE_AFTER_SECONDS);
        $this->acceptOnlyTokens($this->currentToken());
        $authCalls = $this->authCalls();
        $this->renew($factory);

        self::assertSame(1, $this->authCallsSince($authCalls));
        self::assertSame($clientId, $this->clientId($redis));
        $this->assertStoredTokenIsFresh($redis);
    }

    public function testConnectionAgedElevenHoursReauthenticates(): void
    {
        $factory = $this->connectionFactory();
        $redis = $this->openConnection($factory);
        $clientId = $this->clientId($redis);

        $this->clock->advance(self::ELEVEN_HOURS);
        $this->acceptOnlyTokens($this->currentToken());
        $this->renew($factory);

        self::assertNotSame($clientId, $this->clientId($redis));
        self::assertSame($this->userId, $redis->rawCommand('ACL', 'WHOAMI'));
        self::assertSame(0, $this->metricsEmitter->count());
    }

    public function testTokenIsRegeneratedWhenCredentialsRotate(): void
    {
        $factory = $this->connectionFactory();
        $redis = $this->openConnection($factory);
        $tokenBeforeRotation = $this->currentToken();

        $this->credentials = $this->newCredentials();
        $this->clock->advance(RedisIamConnection::REAUTHENTICATE_AFTER_SECONDS);
        $rotatedToken = $this->currentToken();
        $this->acceptOnlyTokens($rotatedToken);
        $this->renew($factory);

        self::assertNotSame($tokenBeforeRotation, $rotatedToken);
        self::assertStringContainsString($this->credentials->getAccessKeyId(), $rotatedToken);
        self::assertSame($this->userId, $redis->rawCommand('ACL', 'WHOAMI'));
        $this->assertStoredTokenIsFresh($redis);
    }

    public function testNeverReauthenticatesInsideMulti(): void
    {
        $factory = $this->connectionFactory();
        $redis = $this->openConnection($factory);
        $this->clock->advance(RedisIamConnection::REAUTHENTICATE_AFTER_SECONDS);
        $this->acceptOnlyTokens($this->currentToken());
        $authCalls = $this->authCalls();

        $redis->multi();
        $redis->set($this->faker->uuid(), $this->faker->word());
        $this->renew($factory);
        $redis->exec();

        self::assertSame(0, $this->authCallsSince($authCalls));
        $this->renew($factory);
        self::assertSame(1, $this->authCallsSince($authCalls));
    }

    private function openConnection(RedisIamConnectionFactory $factory): \Redis
    {
        $this->acceptOnlyTokens($this->currentToken());

        return $factory->create($this->dsn);
    }

    private function renew(RedisIamConnectionFactory $factory): void
    {
        (new RedisIamConnectionRenewalSubscriber($factory))->renewDueConnections();
    }

    private function assertStoredTokenIsFresh(\Redis $redis): void
    {
        $this->admin->rawCommand('CLIENT', 'KILL', 'ID', (string) $this->clientId($redis));

        self::assertSame($this->userId, $redis->rawCommand('ACL', 'WHOAMI'));
        self::assertSame(0, $this->metricsEmitter->count());
    }

    private function authCallsSince(int $previousCalls): int
    {
        return $this->authCalls() - $previousCalls;
    }

    private function authCalls(): int
    {
        $stats = $this->admin->info('commandstats');
        preg_match('/calls=(\d+)/', (string) ($stats['cmdstat_auth'] ?? 'calls=0'), $matches);

        return (int) $matches[1];
    }
}
