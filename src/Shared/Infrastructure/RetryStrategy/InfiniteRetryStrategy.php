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

    /**
     * @psalm-suppress UnusedParam Parameters required by interface but not used (always retry)
     *
     * @return true
     */
    public function shouldRetry(
        AsyncContext $context,
        ?string $responseContent,
        ?TransportExceptionInterface $exception
    ): bool {
        return true;
    }

    /**
     * @psalm-suppress UnusedParam Parameters required by interface but not used (fixed delay)
     */
    public function getDelay(
        AsyncContext $context,
        ?string $responseContent,
        ?TransportExceptionInterface $exception
    ): int {
        return $this->delayMs;
    }

    /**
     * @return true
     */
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
        return $this->delayMs;
    }
}
