<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\RetryStrategy;

use Symfony\Component\HttpClient\Response\AsyncContext;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Retry\RetryStrategyInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

final class InfiniteRetryStrategy implements RetryStrategyInterface
{
    public function __construct(
        private readonly int $delayMs,
    ) {
    }

    public function shouldRetry(
        AsyncContext $context,
        ?string $responseContent,
        ?TransportExceptionInterface $exception
    ): ?bool {
        return true;
    }

    public function getDelay(
        AsyncContext $context,
        ?string $responseContent,
        ?TransportExceptionInterface $exception
    ): int {
        return $this->delayMs;
    }

    public function isRetryable(
        Envelope $message,
        ?\Throwable $throwable = null
    ): bool {
        return true;
    }

    public function getWaitingTime(
        Envelope $message,
        ?\Throwable $throwable = null
    ): int {
        return $this->delayMs;
    }
}
