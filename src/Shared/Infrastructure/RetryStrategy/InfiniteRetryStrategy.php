<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\RetryStrategy;

use Symfony\Component\HttpClient\Response\AsyncContext;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Retry\RetryStrategyInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

final class InfiniteRetryStrategy implements RetryStrategyInterface
{
    public function shouldRetry(
        AsyncContext $_context,
        ?string $_responseContent,
        ?TransportExceptionInterface $_exception
    ): ?bool {
        return true;
    }

    public function getDelay(
        AsyncContext $_context,
        ?string $_responseContent,
        ?TransportExceptionInterface $_exception
    ): int {
        return 60000;
    }

    #[\Override]
    public function isRetryable(
        Envelope $message,
        ?\Throwable $throwable = null
    ): bool {
        return true;
    }

    #[\Override]
    public function getWaitingTime(
        Envelope $message,
        ?\Throwable $throwable = null
    ): int {
        return 60000;
    }
}
