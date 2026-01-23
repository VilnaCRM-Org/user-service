<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventDispatcher;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Base class for event subscribers with automatic error handling.
 *
 * Ensures that failures in observability/non-critical subscribers
 * do not break the main application flow.
 *
 * Subscribers extending this class should use safeExecute() to wrap
 * their event handling logic, which provides automatic try-catch
 * with detailed error logging.
 */
abstract readonly class ResilientEventSubscriber implements EventSubscriberInterface
{
    public function __construct(protected LoggerInterface $logger)
    {
    }

    /**
     * Safely executes event handler logic with automatic error handling.
     *
     * If the handler throws an exception, it will be caught and logged,
     * but the exception will not propagate to break the event flow.
     *
     * @param callable $handler The event handler logic to execute
     * @param string $eventName The name of the event being handled
     */
    protected function safeExecute(callable $handler, string $eventName): void
    {
        try {
            $handler();
        } catch (\Throwable $e) {
            $this->logger->error('Event subscriber execution failed', [
                'subscriber' => static::class,
                'event' => $eventName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
