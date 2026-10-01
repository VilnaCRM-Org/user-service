<?php

declare(strict_types=1);

namespace App\Tests\Integration\Shared\Infrastructure\Adapter;

use Symfony\Component\Cache\Exception\InvalidArgumentException;

final class RedisIamAuthenticationTest extends RedisIamIntegrationTestCase
{
    public function testAuthenticatesWithIamTokenOverTls(): void
    {
        $this->acceptOnlyTokens($this->currentToken());
        $key = $this->faker->uuid();
        $value = $this->faker->sentence();

        $redis = $this->connectionFactory()->create($this->dsn);

        self::assertSame($this->userId, $redis->rawCommand('ACL', 'WHOAMI'));
        self::assertTrue($redis->setex($key, 60, $value));
        self::assertSame($value, $redis->get($key));
        self::assertSame(0, $this->metricsEmitter->count());
        $redis->del($key);
    }

    public function testExpiredTokenFailsAndCountsAuthFailure(): void
    {
        $this->acceptOnlyTokens($this->currentToken());
        $this->clock->advance(-16 * 60);
        $expiredToken = $this->currentToken();

        $this->assertConnectionIsRejected();
        self::assertFalse($this->logsContain($expiredToken));
    }

    public function testWrongUserTokenFailsAndCountsAuthFailure(): void
    {
        $this->acceptOnlyTokens($this->currentToken());
        $wrongUserId = strtolower($this->faker->lexify('user-service-it-other-????'));

        $this->assertConnectionIsRejected($wrongUserId);
    }

    public function testDefaultUserIsOff(): void
    {
        $connection = $this->unauthenticatedConnection();

        try {
            $connection->ping();
            self::fail('The Valkey default user accepted an unauthenticated command.');
        } catch (\RedisException $exception) {
            self::assertStringContainsString('NOAUTH', $exception->getMessage());
        } finally {
            $connection->close();
        }

        self::assertContains('off', $this->admin->rawCommand('ACL', 'GETUSER', 'default')[1]);
    }

    public function testPlainTcpDsnIsRefusedBeforeConnecting(): void
    {
        $this->acceptOnlyTokens($this->currentToken());

        $this->expectException(InvalidArgumentException::class);

        $this->connectionFactory()->create(str_replace('rediss://', 'redis://', $this->dsn));
    }

    private function assertConnectionIsRejected(?string $userId = null): void
    {
        $factory = $this->connectionFactory($userId);

        try {
            $factory->create($this->dsn);
            self::fail('IAM authentication with an invalid token succeeded.');
        } catch (\RedisException $exception) {
            self::assertStringContainsString('WRONGPASS', $exception->getMessage());
        }

        self::assertSame(1, $this->metricsEmitter->count());
        $metric = $this->metricsEmitter->emitted()->all()[0];
        self::assertSame('auth_failure', $metric->name());
        self::assertSame(
            ['backend' => 'redis'],
            $metric->dimensions()->values()->toAssociativeArray()
        );
        self::assertSame([], $factory->connections());
        self::assertTrue($this->logs->hasErrorThatContains('Redis IAM authentication failed.'));
    }

    private function logsContain(string $needle): bool
    {
        foreach ($this->logs->getRecords() as $record) {
            if (str_contains($record->message . var_export($record->context, true), $needle)) {
                return true;
            }
        }

        return false;
    }
}
