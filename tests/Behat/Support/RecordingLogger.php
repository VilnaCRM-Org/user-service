<?php

declare(strict_types=1);

namespace App\Tests\Behat\Support;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Stringable;
use Symfony\Contracts\Service\ResetInterface;

final class RecordingLogger extends AbstractLogger implements ResetInterface
{
    /**
     * @var list<array{
     *     level: string,
     *     message: string,
     *     context: array<string, array|bool|float|int|object|string|null>
     * }>
     */
    private array $records = [];

    public function __construct(
        private readonly LoggerInterface $innerLogger,
    ) {
    }

    /**
     * @param string $level
     * @param array<string, array|bool|float|int|object|string|null> $context
     */
    #[\Override]
    public function log($level, Stringable|string $message, array $context = []): void
    {
        $this->records[] = [
            'level' => (string) $level,
            'message' => (string) $message,
            'context' => $context,
        ];

        $this->innerLogger->log($level, $message, $context);
    }

    /**
     * @return list<array{
     *     level: string,
     *     message: string,
     *     context: array<string, array|bool|float|int|object|string|null>
     * }>
     */
    public function records(): array
    {
        return $this->records;
    }

    public function clear(): void
    {
        $this->records = [];
    }

    #[\Override]
    public function reset(): void
    {
        $this->clear();

        if ($this->innerLogger instanceof ResetInterface) {
            $this->innerLogger->reset();
        }
    }
}
