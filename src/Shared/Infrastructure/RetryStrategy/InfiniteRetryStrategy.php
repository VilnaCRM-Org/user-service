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
        AsyncContext $_context,
        ?string $_responseContent,
        ?TransportExceptionInterface $_exception
    ): bool {
        return true;
    }

    public function getDelay(
        AsyncContext $_context,
        ?string $_responseContent,
        ?TransportExceptionInterface $_exception
    ): int {
        return $this->delayMs;
    }

    #[\Override]
    public function isRetryable(
        Envelope $_message,
        ?\Throwable $_throwable = null
    ): bool {
        return true;
    }

    #[\Override]
    public function getWaitingTime(
        Envelope $_message,
        ?\Throwable $_throwable = null
    ): int {
        return $this->delayMs;
    }
}
