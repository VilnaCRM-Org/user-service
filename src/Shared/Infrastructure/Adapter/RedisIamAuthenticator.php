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
 * and is rethrown: there is no unauthenticated fallback. The failure log
 * never contains the token; it contains the error message only for Redis
 * errors, because credential-provider errors can carry the ECS task
 * credentials endpoint path.
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
    public function authenticate(Redis $client): int
    {
        try {
            return $this->sendAuth($client);
        } catch (\Throwable $exception) {
            $client->close();
            $this->reportFailure($exception);

            throw $exception;
        }
    }

    private function sendAuth(Redis $client): int
    {
        $token = $this->tokenFactory->create();

        if ($client->auth([$this->userId, $token->value()]) !== true) {
            throw new RedisException('Redis rejected the IAM authentication request.');
        }

        return $token->validUntil();
    }

    private function reportFailure(\Throwable $exception): void
    {
        $this->metricsEmitter->emit($this->authFailureMetricFactory->create(self::BACKEND));
        $this->logger->error('Redis IAM authentication failed.', $this->failureContext($exception));
    }

    /**
     * @return array<string, string>
     */
    private function failureContext(\Throwable $exception): array
    {
        $context = [
            'backend' => self::BACKEND,
            'user_id' => $this->userId,
            'exception_class' => $exception::class,
        ];

        if ($exception instanceof RedisException) {
            $context['error'] = $exception->getMessage();
        }

        return $context;
    }
}
