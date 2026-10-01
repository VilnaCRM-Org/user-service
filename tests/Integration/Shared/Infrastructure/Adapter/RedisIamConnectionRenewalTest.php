<?php

declare(strict_types=1);

namespace App\Tests\Integration\Shared\Infrastructure\Adapter;

use App\Shared\Infrastructure\Adapter\RedisIamConnection;
use App\Shared\Infrastructure\EventSubscriber\RedisIamConnectionRenewalSubscriber;
use App\Shared\Infrastructure\Factory\RedisIamConnectionFactory;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Messenger\Event\WorkerRunningEvent;
use Symfony\Component\Messenger\Event\WorkerStartedEvent;
use Symfony\Component\Messenger\EventListener\StopWorkerOnRestartSignalListener;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Worker;

final class RedisIamConnectionRenewalTest extends RedisIamIntegrationTestCase
{
    private const ELEVEN_HOURS = 39_600;
    private const SIXTEEN_MINUTES = 960;

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

    public function testReauthenticatesOneMinuteBeforeSigningCredentialsExpire(): void
    {
        $this->credentials = $this->newCredentials(120);
        $factory = $this->connectionFactory();
        $redis = $this->openConnection($factory);
        $clientId = $this->clientId($redis);

        $this->clock->advance(59);
        $authCalls = $this->authCalls();
        $this->renew($factory);
        self::assertSame(0, $this->authCallsSince($authCalls));

        $this->clock->advance(1);
        $this->credentials = $this->newCredentials(3600);
        $this->acceptOnlyTokens($this->currentToken());
        $this->renew($factory);

        self::assertSame(1, $this->authCallsSince($authCalls));
        self::assertSame($clientId, $this->clientId($redis));
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

    public function testIdleWorkerTickRenewsConnectionBeforeRestartSignalCheck(): void
    {
        $factory = $this->connectionFactory();
        $redis = $this->openConnection($factory);
        $dispatcher = $this->workerEventDispatcher($factory, $redis);
        $worker = new Worker([], $this->createMock(MessageBusInterface::class), $dispatcher);
        $dispatcher->dispatch(new WorkerStartedEvent($worker));

        $this->clock->advance(self::SIXTEEN_MINUTES);
        $this->acceptOnlyTokens($this->currentToken());
        $this->admin->rawCommand('CLIENT', 'KILL', 'ID', (string) $this->clientId($redis));
        $authCalls = $this->authCalls();
        $dispatcher->dispatch(new WorkerRunningEvent($worker, true));

        self::assertSame($this->userId, $redis->rawCommand('ACL', 'WHOAMI'));
        self::assertSame(0, $this->metricsEmitter->count());
        self::assertSame(1, $this->authCallsSince($authCalls));
    }

    public function testRejectedReauthenticationIsCountedAsAuthFailure(): void
    {
        $factory = $this->connectionFactory();
        $this->openConnection($factory);
        $this->clock->advance(RedisIamConnection::REAUTHENTICATE_AFTER_SECONDS);
        $this->acceptOnlyTokens($this->faker->sha256());

        try {
            $this->renew($factory);
            self::fail('The rejected re-authentication was not rethrown.');
        } catch (\RedisException $exception) {
            self::assertStringContainsString('WRONGPASS', $exception->getMessage());
        }

        self::assertSame(1, $this->metricsEmitter->count());
        self::assertTrue($this->logs->hasErrorThatContains('Redis IAM authentication failed.'));
    }

    public function testConnectionErrorDuringReauthenticationIsLoggedButNotCounted(): void
    {
        $factory = $this->connectionFactory();
        $this->openConnection($factory);
        $this->clock->advance(RedisIamConnection::REAUTHENTICATE_AFTER_SECONDS);
        $this->acceptOnlyTokens($this->currentToken());
        $this->admin->rawCommand('CLIENT', 'PAUSE', '4000', 'ALL');

        try {
            $this->renew($factory);
            self::fail('The connection error was not rethrown.');
        } catch (\RedisException) {
        }

        self::assertSame(0, $this->metricsEmitter->count());
        self::assertFalse($this->logs->hasErrorThatContains('Redis IAM authentication failed.'));
        self::assertTrue(
            $this->logs->hasWarningThatContains('Redis IAM connection error during authentication.')
        );
    }

    public function testUnreachableEndpointIsLoggedButNotCounted(): void
    {
        try {
            $this->connectionFactory()->create('rediss://127.0.0.1:1');
            self::fail('The unreachable endpoint was not reported.');
        } catch (\RedisException) {
        }

        self::assertSame(0, $this->metricsEmitter->count());
        self::assertTrue($this->logs->hasWarningThatContains('Redis IAM connection failed.'));
    }

    private function workerEventDispatcher(
        RedisIamConnectionFactory $factory,
        \Redis $redis
    ): EventDispatcher {
        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new RedisIamConnectionRenewalSubscriber($factory));
        $dispatcher->addSubscriber(new StopWorkerOnRestartSignalListener(
            new RedisAdapter($redis, $this->faker->lexify('it????'))
        ));

        return $dispatcher;
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
