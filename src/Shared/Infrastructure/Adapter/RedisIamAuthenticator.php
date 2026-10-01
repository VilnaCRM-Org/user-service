<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Adapter;

use App\Shared\Application\Observability\Emitter\BusinessMetricsEmitterInterface;
use App\Shared\Application\Observability\Factory\AuthFailureMetricFactoryInterface;
use App\Shared\Infrastructure\Factory\RedisIamAuthTokenFactoryInterface;
use Psr\Log\LoggerInterface;
use Redis;
use RedisException;

/**
 * Sends AUTH <user-id> <IAM token> with a freshly created token.
 * Any failure closes the connection, counts auth_failure{backend=redis}
 * and is rethrown: there is no unauthenticated fallback.
 */
final readonly class RedisIamAuthenticator implements RedisIamAuthenticatorInterface
{
    private const BACKEND = 'redis';

    public function __construct(
        private RedisIamAuthTokenFactoryInterface $tokenFactory,
        private BusinessMetricsEmitterInterface $metricsEmitter,
        private AuthFailureMetricFactoryInterface $authFailureMetricFactory,
        private LoggerInterface $logger,
        private string $userId
    ) {
    }

    #[\Override]
    public function authenticate(Redis $client): void
    {
        try {
            $this->sendAuth($client);
        } catch (\Throwable $exception) {
            $client->close();
            $this->reportFailure($exception);

            throw $exception;
        }
    }

    private function sendAuth(Redis $client): void
    {
        if ($client->auth([$this->userId, $this->tokenFactory->create()]) !== true) {
            throw new RedisException('Redis rejected the IAM authentication request.');
        }
    }

    private function reportFailure(\Throwable $exception): void
    {
        $this->metricsEmitter->emit($this->authFailureMetricFactory->create(self::BACKEND));
        $this->logger->error('Redis IAM authentication failed.', [
            'backend' => self::BACKEND,
            'user_id' => $this->userId,
            'exception_class' => $exception::class,
            'error' => $exception->getMessage(),
        ]);
    }
}
