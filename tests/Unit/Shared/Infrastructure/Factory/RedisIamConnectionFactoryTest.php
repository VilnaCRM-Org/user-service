<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Factory;

use App\Shared\Application\Provider\CurrentTimestampProviderInterface;
use App\Shared\Infrastructure\Adapter\RedisIamAuthenticatorInterface;
use App\Shared\Infrastructure\Factory\RedisClientFactoryInterface;
use App\Shared\Infrastructure\Factory\RedisIamConnectionFactory;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Exception\InvalidArgumentException;

final class RedisIamConnectionFactoryTest extends UnitTestCase
{
    private \Redis&MockObject $client;
    private RedisClientFactoryInterface&MockObject $clientFactory;
    private RedisIamAuthenticatorInterface&MockObject $authenticator;
    private LoggerInterface&MockObject $logger;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->client = $this->createMock(\Redis::class);
        $this->clientFactory = $this->createMock(RedisClientFactoryInterface::class);
        $this->clientFactory->method('create')->willReturn($this->client);
        $this->authenticator = $this->createMock(RedisIamAuthenticatorInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testOpensAuthenticatedTlsConnectionForRedissDsn(): void
    {
        $host = $this->faker->domainName();
        $port = $this->faker->numberBetween(1024, 65535);
        $options = ['verify_peer' => true, 'verify_peer_name' => true];
        $this->client->expects(self::once())->method('connect')
            ->with('tls://' . $host, $port, 2.0, null, 0, 2.0, ['stream' => $options])
            ->willReturn(true);
        $this->authenticator->expects(self::once())->method('authenticate')->with($this->client);
        $factory = $this->factory($options);

        $connection = $factory->create(sprintf('rediss://%s:%d', $host, $port));

        self::assertSame($this->client, $connection);
        self::assertCount(1, $factory->connections());
        self::assertSame($this->client, $factory->connections()[0]->client());
    }

    public function testConnectionsLogConnectFailuresThroughTheInjectedLogger(): void
    {
        $this->client->method('connect')->willReturn(false);
        $this->logger->expects(self::once())->method('warning')
            ->with('Redis IAM connection failed.');

        $this->expectException(\RedisException::class);

        $this->factory()->create(sprintf('rediss://%s:6379', $this->faker->domainName()));
    }

    public function testAcceptsTrailingSlashAndDefaultsToPort6379(): void
    {
        $host = $this->faker->domainName();
        $this->client->expects(self::once())->method('connect')
            ->with('tls://' . $host, 6379)
            ->willReturn(true);

        $this->factory()->create(sprintf('rediss://%s/', $host));
    }

    public function testDoesNotTrackConnectionThatFailedToAuthenticate(): void
    {
        $this->client->method('connect')->willReturn(true);
        $this->authenticator->method('authenticate')
            ->willThrowException(new \RedisException('WRONGPASS'));
        $factory = $this->factory();

        try {
            $factory->create(sprintf('rediss://%s:6379', $this->faker->domainName()));
            self::fail('Authentication failure was not rethrown.');
        } catch (\RedisException $exception) {
            self::assertSame('WRONGPASS', $exception->getMessage());
        }

        self::assertSame([], $factory->connections());
    }

    #[DataProvider('unsupportedDsnProvider')]
    public function testRejectsDsnThatIsNotPlainTlsHostAndPort(string $dsn): void
    {
        $this->clientFactory->expects(self::never())->method('create');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Redis IAM requires rediss://<host>:<port> without credentials, options or a database.'
        );

        $this->factory()->create($dsn);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function unsupportedDsnProvider(): iterable
    {
        yield 'plain TCP' => ['redis://cache.internal:6379'];
        yield 'password' => ['rediss://:secret@cache.internal:6379'];
        yield 'user and password' => ['rediss://app:secret@cache.internal:6379'];
        yield 'database path' => ['rediss://cache.internal:6379/0'];
        yield 'options' => ['rediss://cache.internal:6379?timeout=1'];
        yield 'missing host' => ['rediss://:6379'];
        yield 'port too long' => ['rediss://cache.internal:123456'];
        yield 'prefixed' => [' rediss://cache.internal:6379'];
        yield 'suffixed' => ['rediss://cache.internal:6379 '];
    }

    /**
     * @param array<string, bool|string> $tlsStreamOptions
     */
    private function factory(array $tlsStreamOptions = []): RedisIamConnectionFactory
    {
        return new RedisIamConnectionFactory(
            $this->clientFactory,
            $this->authenticator,
            $this->createMock(CurrentTimestampProviderInterface::class),
            $tlsStreamOptions,
            $this->logger
        );
    }
}
