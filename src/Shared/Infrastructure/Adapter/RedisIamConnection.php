<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Adapter;

use App\Shared\Application\Provider\CurrentTimestampProviderInterface;
use App\Shared\Infrastructure\Factory\RedisIamAuthTokenFactory;
use Redis;
use RedisException;

/**
 * One TLS phpredis connection authenticated with an ElastiCache IAM token.
 *
 * phpredis keeps the last AUTH arguments and replays them when it reconnects
 * transparently, so the stored token is renewed at safe points (outside
 * MULTI/EXEC, pipelines and Lua) before it expires: re-AUTH on the open
 * connection after 10 minutes, a new connection once the stored token may be
 * expired. A used connection is therefore re-authenticated long before the
 * ElastiCache 12-hour limit (and the 11-hour cap of AD-02).
 */
final class RedisIamConnection
{
    public const REAUTHENTICATE_AFTER_SECONDS = 600;

    private ?int $authenticatedAt = null;

    /**
     * @param array<string, bool|string> $tlsStreamOptions
     */
    public function __construct(
        private readonly Redis $client,
        private readonly string $host,
        private readonly int $port,
        private readonly array $tlsStreamOptions,
        private readonly RedisIamAuthenticatorInterface $authenticator,
        private readonly CurrentTimestampProviderInterface $timestampProvider
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

        $authenticationAge = $this->authenticationAge();

        if (
            $this->authenticatedAt === null
            || $authenticationAge >= RedisIamAuthTokenFactory::TOKEN_LIFETIME_SECONDS
        ) {
            $this->open();

            return;
        }

        if ($authenticationAge >= self::REAUTHENTICATE_AFTER_SECONDS) {
            $this->authenticate();
        }
    }

    private function authenticationAge(): int
    {
        return $this->timestampProvider->currentTimestamp() - $this->authenticatedAt;
    }

    private function connect(): void
    {
        $connected = $this->client->connect(
            'tls://' . $this->host,
            $this->port,
            0.0,
            null,
            0,
            0.0,
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
        $this->authenticatedAt = null;
        $this->authenticator->authenticate($this->client);
        $this->authenticatedAt = $this->timestampProvider->currentTimestamp();
    }
}
