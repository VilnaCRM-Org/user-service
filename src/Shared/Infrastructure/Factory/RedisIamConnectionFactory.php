<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Factory;

use App\Shared\Application\Provider\CurrentTimestampProviderInterface;
use App\Shared\Infrastructure\Adapter\RedisIamAuthenticatorInterface;
use App\Shared\Infrastructure\Adapter\RedisIamConnection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Exception\InvalidArgumentException;

/**
 * Opens TLS connections authenticated with an ElastiCache IAM token and keeps
 * them so that they can be re-authenticated at safe points.
 */
final class RedisIamConnectionFactory implements RedisConnectionFactoryInterface
{
    private const DSN_PATTERN = '#^rediss://(?<host>[A-Za-z0-9.-]+)(?::(?<port>[0-9]{1,5}))?/?$#';
    private const DEFAULT_PORT = 6379;
    private const INVALID_DSN_MESSAGE =
        'Redis IAM requires rediss://<host>:<port> without credentials, options or a database.';

    /** @var list<RedisIamConnection> */
    private array $connections = [];

    /**
     * @param array<string, bool|string> $tlsStreamOptions
     */
    public function __construct(
        private readonly RedisClientFactoryInterface $clientFactory,
        private readonly RedisIamAuthenticatorInterface $authenticator,
        private readonly CurrentTimestampProviderInterface $timestampProvider,
        private readonly array $tlsStreamOptions,
        private readonly LoggerInterface $logger
    ) {
    }

    #[\Override]
    public function create(string $dsn): \Redis
    {
        $endpoint = $this->endpoint($dsn);
        $connection = new RedisIamConnection(
            $this->clientFactory->create(),
            $endpoint['host'],
            $endpoint['port'],
            $this->tlsStreamOptions,
            $this->authenticator,
            $this->timestampProvider,
            $this->logger
        );
        $connection->open();
        $this->connections[] = $connection;

        return $connection->client();
    }

    /**
     * @return list<RedisIamConnection>
     */
    public function connections(): array
    {
        return $this->connections;
    }

    /**
     * @return array{host: string, port: int}
     */
    private function endpoint(string $dsn): array
    {
        if (preg_match(self::DSN_PATTERN, $dsn, $matches) !== 1) {
            throw new InvalidArgumentException(self::INVALID_DSN_MESSAGE);
        }

        return [
            'host' => $matches['host'],
            'port' => (int) ($matches['port'] ?? self::DEFAULT_PORT),
        ];
    }
}
