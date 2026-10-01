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
 * Any failure closes the connection and is rethrown: there is no
 * unauthenticated fallback. auth_failure{backend=redis} is counted only for
 * authentication failures: the server rejecting the credentials (a
 * WRONGPASS/NOAUTH/NOPERM reply or a non-true AUTH reply) and a token that
 * cannot be created (signing or credential errors). Connection and transport
 * errors during AUTH (timeouts, resets, a server that is loading) are logged
 * as a distinct warning event and are not counted, so the metric matches the
 * ElastiCache AuthenticationFailures count. The logs never contain the token;
 * they contain the error message only for Redis errors, because
 * credential-provider errors can carry the ECS task credentials endpoint path.
 */
final readonly class RedisIamAuthenticator implements RedisIamAuthenticatorInterface
{
    private const BACKEND = 'redis';
    private const REJECTION_PATTERN =
        '/^(?:WRONGPASS|NOAUTH|NOPERM)\b|^ERR\b.*\bAUTH\b|^AUTH failed|invalid username-password/';

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
            $token = $this->tokenFactory->create();
        } catch (\Throwable $exception) {
            $this->closeQuietly($client);
            $this->reportAuthenticationFailure($exception);

            throw $exception;
        }

        return $this->sendAuth($client, $token);
    }

    private function sendAuth(Redis $client, RedisIamAuthToken $token): int
    {
        try {
            $accepted = $client->auth([$this->userId, $token->value()]);
        } catch (\Throwable $exception) {
            $this->closeQuietly($client);
            $this->reportAuthError($exception);

            throw $exception;
        }

        if ($accepted !== true) {
            $this->closeQuietly($client);
            $exception = new RedisException('Redis rejected the IAM authentication request.');
            $this->reportAuthenticationFailure($exception);

            throw $exception;
        }

        return $token->validUntil();
    }

    private function reportAuthError(\Throwable $exception): void
    {
        if ($this->isRejection($exception)) {
            $this->reportAuthenticationFailure($exception);

            return;
        }

        $this->logger->warning(
            'Redis IAM connection error during authentication.',
            $this->failureContext($exception)
        );
    }

    private function isRejection(\Throwable $exception): bool
    {
        return $exception instanceof RedisException
            && preg_match(self::REJECTION_PATTERN, $exception->getMessage()) === 1;
    }

    private function closeQuietly(Redis $client): void
    {
        try {
            $client->close();
        } catch (\Throwable) {
            // The original failure is what matters; a failing close must not mask it.
        }
    }

    private function reportAuthenticationFailure(\Throwable $exception): void
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
