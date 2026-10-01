<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Adapter;

use App\Shared\Application\Provider\CurrentTimestampProviderInterface;
use Psr\Log\LoggerInterface;
use Redis;
use RedisException;

/**
 * One TLS phpredis connection authenticated with an ElastiCache IAM token.
 *
 * phpredis keeps the last AUTH arguments and replays them when it reconnects
 * transparently, so the stored token is renewed at safe points (outside
 * MULTI/EXEC, pipelines and Lua; see RedisIamConnectionRenewalSubscriber)
 * before it expires. The token is valid until the shorter of 15 minutes and
 * the expiry of the signing credentials (validUntil). The connection is
 * re-authenticated with a fresh token on the open connection at
 * min(authenticatedAt + 10 minutes, validUntil - 60 seconds), the 60 seconds
 * matching the AWS SDK credential refresh window, and replaced by a new
 * connection at a safe point at or after validUntil. A used connection is
 * therefore re-authenticated long before the ElastiCache 12-hour limit (and
 * the 11-hour cap of AD-02).
 *
 * Connection errors (an unreachable endpoint) are logged as a warning and
 * are not counted as authentication failures; see RedisIamAuthenticator.
 *
 * Connect and read timeouts are 2 seconds: ElastiCache answers in
 * milliseconds inside the VPC, the app sends no blocking commands over these
 * connections, and a renewal that cannot reach Redis must fail the request
 * (HTTP 500) quickly instead of holding it for the 60-second PHP default.
 */
final class RedisIamConnection
{
    public const REAUTHENTICATE_AFTER_SECONDS = 600;
    public const REAUTHENTICATE_BEFORE_EXPIRY_SECONDS = 60;

    private const CONNECT_TIMEOUT_SECONDS = 2.0;
    private const READ_TIMEOUT_SECONDS = 2.0;

    private ?int $tokenValidUntil = null;
    private int $reauthenticateAt = PHP_INT_MAX;

    /**
     * @param array<string, bool|string> $tlsStreamOptions
     */
    public function __construct(
        private readonly Redis $client,
        private readonly string $host,
        private readonly int $port,
        private readonly array $tlsStreamOptions,
        private readonly RedisIamAuthenticatorInterface $authenticator,
        private readonly CurrentTimestampProviderInterface $timestampProvider,
        private readonly LoggerInterface $logger
    ) {
    }

    public function client(): Redis
    {
        return $this->client;
    }

    public function open(): void
    {
        $this->connect();
        $this->authenticate();
    }

    public function renewIfDue(): void
    {
        if ($this->client->getMode() !== Redis::ATOMIC) {
            return;
        }

        $now = $this->timestampProvider->currentTimestamp();

        if ($this->tokenValidUntil === null || $now >= $this->tokenValidUntil) {
            $this->open();

            return;
        }

        if ($now >= $this->reauthenticateAt) {
            $this->authenticate();
        }
    }

    private function connect(): void
    {
        try {
            $this->tryConnect();
        } catch (RedisException $exception) {
            $this->logger->warning('Redis IAM connection failed.', [
                'backend' => 'redis',
                'host' => $this->host,
                'port' => $this->port,
                'exception_class' => $exception::class,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    private function tryConnect(): void
    {
        $connected = $this->client->connect(
            'tls://' . $this->host,
            $this->port,
            self::CONNECT_TIMEOUT_SECONDS,
            null,
            0,
            self::READ_TIMEOUT_SECONDS,
            ['stream' => $this->tlsStreamOptions]
        );

        if ($connected !== true) {
            throw new RedisException(
                sprintf('Redis IAM connection to %s:%d failed.', $this->host, $this->port)
            );
        }
    }

    private function authenticate(): void
    {
        $this->tokenValidUntil = null;
        $tokenValidUntil = $this->authenticator->authenticate($this->client);
        $this->reauthenticateAt = min(
            $this->timestampProvider->currentTimestamp() + self::REAUTHENTICATE_AFTER_SECONDS,
            $tokenValidUntil - self::REAUTHENTICATE_BEFORE_EXPIRY_SECONDS
        );
        $this->tokenValidUntil = $tokenValidUntil;
    }
}
