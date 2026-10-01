<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Adapter;

use App\Shared\Application\Provider\CurrentTimestampProviderInterface;
use App\Shared\Infrastructure\Adapter\RedisIamAuthenticatorInterface;
use App\Shared\Infrastructure\Adapter\RedisIamConnection;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

final class RedisIamConnectionTest extends UnitTestCase
{
    private const ELEVEN_HOURS = 39_600;
    private const TOKEN_LIFETIME = 900;
    private const SHORT_LIVED_CREDENTIALS = 120;

    private \Redis&MockObject $client;
    private RedisIamAuthenticatorInterface&MockObject $authenticator;
    private LoggerInterface&MockObject $logger;
    private string $host;
    private int $port;
    private int $now;
    private int $connects = 0;
    private int $authentications = 0;
    private int $tokenValidity = self::TOKEN_LIFETIME;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->client = $this->createMock(\Redis::class);
        $this->authenticator = $this->createMock(RedisIamAuthenticatorInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->host = $this->faker->domainName();
        $this->port = $this->faker->numberBetween(1024, 65535);
        $this->now = $this->faker->numberBetween(1_700_000_000, 1_900_000_000);
        $this->client->method('getMode')->willReturn(\Redis::ATOMIC);
    }

    public function testOpenConnectsOverTlsWithTwoSecondTimeoutsAndAuthenticates(): void
    {
        $options = ['verify_peer' => true, 'cafile' => $this->faker->filePath()];
        $this->client->expects(self::once())->method('connect')
            ->with('tls://' . $this->host, $this->port, 2.0, null, 0, 2.0, ['stream' => $options])
            ->willReturn(true);
        $this->authenticator->expects(self::once())->method('authenticate')->with($this->client);

        $this->connection($options)->open();
    }

    public function testOpenFailsWithoutAuthenticatingWhenConnectFails(): void
    {
        $this->client->method('connect')->willReturn(false);
        $this->authenticator->expects(self::never())->method('authenticate');

        $this->expectException(\RedisException::class);
        $this->expectExceptionMessage(
            sprintf('Redis IAM connection to %s:%d failed.', $this->host, $this->port)
        );

        $this->connection()->open();
    }

    public function testConnectFailureIsLoggedWithoutCountingAnAuthenticationFailure(): void
    {
        $this->client->method('connect')->willReturn(false);
        $this->authenticator->expects(self::never())->method('authenticate');
        $this->logger->expects(self::never())->method('error');
        $this->logger->expects(self::once())->method('warning')->with(
            'Redis IAM connection failed.',
            [
                'backend' => 'redis',
                'host' => $this->host,
                'port' => $this->port,
                'exception_class' => \RedisException::class,
                'error' => sprintf(
                    'Redis IAM connection to %s:%d failed.',
                    $this->host,
                    $this->port
                ),
            ]
        );

        $this->expectException(\RedisException::class);

        $this->connection()->open();
    }

    public function testConnectExceptionIsLoggedAndRethrown(): void
    {
        $exception = new \RedisException('Connection timed out');
        $this->client->method('connect')->willThrowException($exception);
        $this->authenticator->expects(self::never())->method('authenticate');
        $this->logger->expects(self::once())->method('warning')->with(
            'Redis IAM connection failed.',
            [
                'backend' => 'redis',
                'host' => $this->host,
                'port' => $this->port,
                'exception_class' => \RedisException::class,
                'error' => 'Connection timed out',
            ]
        );

        $this->expectExceptionObject($exception);

        $this->connection()->open();
    }

    public function testExposesTheAuthenticatedClient(): void
    {
        self::assertSame($this->client, $this->connection()->client());
    }

    public function testKeepsRecentlyAuthenticatedConnection(): void
    {
        $connection = $this->openedConnection();

        $this->now += RedisIamConnection::REAUTHENTICATE_AFTER_SECONDS - 1;
        $connection->renewIfDue();

        self::assertSame([1, 1], [$this->connects, $this->authentications]);
    }

    public function testReauthenticatesOpenConnectionAfterTenMinutes(): void
    {
        $connection = $this->openedConnection();

        $this->now += RedisIamConnection::REAUTHENTICATE_AFTER_SECONDS;
        $connection->renewIfDue();

        self::assertSame([1, 2], [$this->connects, $this->authentications]);
    }

    public function testRenewalRestartsTheReauthenticationInterval(): void
    {
        $connection = $this->openedConnection();
        $this->now += RedisIamConnection::REAUTHENTICATE_AFTER_SECONDS;
        $connection->renewIfDue();

        $this->now += RedisIamConnection::REAUTHENTICATE_AFTER_SECONDS - 1;
        $connection->renewIfDue();

        self::assertSame([1, 2], [$this->connects, $this->authentications]);
    }

    public function testReauthenticatesUpToTheTokenLifetime(): void
    {
        $connection = $this->openedConnection();

        $this->now += 899;
        $connection->renewIfDue();

        self::assertSame([1, 2], [$this->connects, $this->authentications]);
    }

    public function testReconnectsOnceTheStoredTokenMayHaveExpired(): void
    {
        $connection = $this->openedConnection();

        $this->now += 900;
        $connection->renewIfDue();

        self::assertSame([2, 2], [$this->connects, $this->authentications]);
    }

    public function testConnectionAgedElevenHoursReauthenticatesWithNewConnection(): void
    {
        $connection = $this->openedConnection();

        $this->now += self::ELEVEN_HOURS;
        $connection->renewIfDue();

        self::assertSame([2, 2], [$this->connects, $this->authentications]);
    }

    public function testKeepsConnectionUntilOneMinuteBeforeShortLivedTokenExpires(): void
    {
        $this->tokenValidity = self::SHORT_LIVED_CREDENTIALS;
        $connection = $this->openedConnection();

        $this->now += self::SHORT_LIVED_CREDENTIALS - 61;
        $connection->renewIfDue();

        self::assertSame([1, 1], [$this->connects, $this->authentications]);
    }

    public function testReauthenticatesOneMinuteBeforeShortLivedTokenExpires(): void
    {
        $this->tokenValidity = self::SHORT_LIVED_CREDENTIALS;
        $connection = $this->openedConnection();

        $this->now += self::SHORT_LIVED_CREDENTIALS - 60;
        $connection->renewIfDue();

        self::assertSame([1, 2], [$this->connects, $this->authentications]);
    }

    public function testReauthenticatesUpToShortLivedTokenExpiry(): void
    {
        $this->tokenValidity = self::SHORT_LIVED_CREDENTIALS;
        $connection = $this->openedConnection();

        $this->now += self::SHORT_LIVED_CREDENTIALS - 1;
        $connection->renewIfDue();

        self::assertSame([1, 2], [$this->connects, $this->authentications]);
    }

    public function testReconnectsWhenShortLivedTokenExpires(): void
    {
        $this->tokenValidity = self::SHORT_LIVED_CREDENTIALS;
        $connection = $this->openedConnection();

        $this->now += self::SHORT_LIVED_CREDENTIALS;
        $connection->renewIfDue();

        self::assertSame([2, 2], [$this->connects, $this->authentications]);
    }

    public function testReconnectAuthenticatesOnceEvenWhenTheNewTokenIsAboutToExpire(): void
    {
        $this->tokenValidity = 60;
        $connection = $this->openedConnection();

        $this->now += 60;
        $connection->renewIfDue();

        self::assertSame([2, 2], [$this->connects, $this->authentications]);
    }

    public function testRenewalScheduleFollowsTheLatestTokenValidity(): void
    {
        $this->tokenValidity = self::SHORT_LIVED_CREDENTIALS;
        $connection = $this->openedConnection();
        $this->now += self::SHORT_LIVED_CREDENTIALS - 60;
        $this->tokenValidity = self::TOKEN_LIFETIME;
        $connection->renewIfDue();

        $this->now += RedisIamConnection::REAUTHENTICATE_AFTER_SECONDS - 1;
        $connection->renewIfDue();

        self::assertSame([1, 2], [$this->connects, $this->authentications]);
    }

    #[DataProvider('nonAtomicModeProvider')]
    public function testNeverReauthenticatesInsideTransactionOrPipeline(int $mode): void
    {
        $client = $this->createMock(\Redis::class);
        $client->method('connect')->willReturn(true);
        $client->method('getMode')->willReturn($mode);
        $this->authenticator->expects(self::once())->method('authenticate');
        $connection = new RedisIamConnection(
            $client,
            $this->host,
            $this->port,
            [],
            $this->authenticator,
            $this->timestampProvider(),
            $this->logger
        );
        $connection->open();

        $this->now += self::ELEVEN_HOURS;
        $connection->renewIfDue();
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function nonAtomicModeProvider(): iterable
    {
        yield 'MULTI' => [\Redis::MULTI];
        yield 'PIPELINE' => [\Redis::PIPELINE];
    }

    public function testFailedReauthenticationForcesNewConnectionAtNextSafePoint(): void
    {
        $connection = $this->openedConnection(failOnAuthentication: 2);
        $this->now += RedisIamConnection::REAUTHENTICATE_AFTER_SECONDS;

        try {
            $connection->renewIfDue();
            self::fail('Re-authentication failure was not rethrown.');
        } catch (\RedisException $exception) {
            self::assertSame('WRONGPASS', $exception->getMessage());
        }

        $this->now += 1;
        $connection->renewIfDue();

        self::assertSame([2, 3], [$this->connects, $this->authentications]);
    }

    public function testFailedOpenForcesNewConnectionAtNextSafePoint(): void
    {
        $connection = $this->trackedConnection(failOnAuthentication: 1);

        try {
            $connection->open();
            self::fail('Authentication failure was not rethrown.');
        } catch (\RedisException $exception) {
            self::assertSame('WRONGPASS', $exception->getMessage());
        }

        $connection->renewIfDue();

        self::assertSame([2, 2], [$this->connects, $this->authentications]);
    }

    private function openedConnection(int $failOnAuthentication = 0): RedisIamConnection
    {
        $connection = $this->trackedConnection($failOnAuthentication);
        $connection->open();

        return $connection;
    }

    private function trackedConnection(int $failOnAuthentication): RedisIamConnection
    {
        $this->client->method('connect')->willReturnCallback(function (): bool {
            ++$this->connects;

            return true;
        });
        $this->authenticator->method('authenticate')->willReturnCallback(
            function () use ($failOnAuthentication): int {
                ++$this->authentications;
                if ($this->authentications === $failOnAuthentication) {
                    throw new \RedisException('WRONGPASS');
                }

                return $this->now + $this->tokenValidity;
            }
        );

        return $this->connection();
    }

    /**
     * @param array<string, bool|string> $tlsStreamOptions
     */
    private function connection(array $tlsStreamOptions = []): RedisIamConnection
    {
        return new RedisIamConnection(
            $this->client,
            $this->host,
            $this->port,
            $tlsStreamOptions,
            $this->authenticator,
            $this->timestampProvider(),
            $this->logger
        );
    }

    private function timestampProvider(): CurrentTimestampProviderInterface
    {
        $timestampProvider = $this->createMock(CurrentTimestampProviderInterface::class);
        $timestampProvider->method('currentTimestamp')
            ->willReturnCallback(fn (): int => $this->now);

        return $timestampProvider;
    }
}
