<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Event\Async;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;

final class RecordingLogger implements LoggerInterface
{
    use LoggerTrait;

    /**
     * @var array<int, array{string, array<string, mixed>}>
     */
    private array $debugCalls = [];

    /**
     * @param array<string, mixed> $context
     */
    #[\Override]
    public function log(mixed $level, string|\Stringable $message, array $context = []): void
    {
        if ($level === LogLevel::DEBUG) {
            $this->debugCalls[] = [(string) $message, $context];
        }
    }

    /**
     * @return array<int, array{string, array<string, mixed>}>
     */
    public function getDebugCalls(): array
    {
        return $this->debugCalls;
    }
}
