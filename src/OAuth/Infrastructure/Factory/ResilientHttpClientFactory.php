<?php

declare(strict_types=1);

namespace App\OAuth\Infrastructure\Factory;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\RetryMiddleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class ResilientHttpClientFactory
{
    private const MILLISECONDS_PER_SECOND = 1000;

    public function __construct(
        private readonly int $connectTimeoutMs,
        private readonly int $timeoutMs,
        private readonly int $maxRetries,
        private readonly HandlerStack $handlerStack,
        private readonly int $retryDelayBaseMs = self::MILLISECONDS_PER_SECOND,
    ) {
    }

    public function create(): ClientInterface
    {
        $stack = clone $this->handlerStack;
        $stack->push($this->createRetryMiddleware());

        return new Client([
            'handler' => $stack,
            'connect_timeout' => $this->connectTimeoutMs / self::MILLISECONDS_PER_SECOND,
            'timeout' => $this->timeoutMs / self::MILLISECONDS_PER_SECOND,
        ]);
    }

    private function createRetryMiddleware(): callable
    {
        $decider = $this->createRetryDecider();
        $delay = $this->createRetryDelay();

        return static function (callable $handler) use ($decider, $delay): RetryMiddleware {
            return new RetryMiddleware($decider, $handler, $delay);
        };
    }

    private function createRetryDecider(): callable
    {
        $maxRetries = $this->maxRetries;

        return static function (
            int $retries,
            RequestInterface $request,
            ?ResponseInterface $response = null,
            ?\Throwable $exception = null,
        ) use ($maxRetries): bool {
            if ($retries >= $maxRetries) {
                return false;
            }

            if ($exception instanceof \GuzzleHttp\Exception\ConnectException) {
                return true;
            }

            if ($response !== null && $response->getStatusCode() >= 500) {
                return true;
            }

            return false;
        };
    }

    private function createRetryDelay(): callable
    {
        $retryDelayBaseMs = $this->retryDelayBaseMs;

        return static function (int $retries) use ($retryDelayBaseMs): int {
            return $retryDelayBaseMs * (2 ** $retries);
        };
    }
}
