<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\EventDispatcher;

use App\Shared\Infrastructure\EventDispatcher\ResilientEventSubscriber;
use App\Tests\Unit\UnitTestCase;
use Psr\Log\LoggerInterface;

final class ResilientEventSubscriberTest extends UnitTestCase
{
    public function testSafeExecuteCallsHandlerSuccessfully(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('error');

        $subscriber = new class($logger) extends ResilientEventSubscriber {
            public static function getSubscribedEvents(): array
            {
                return [];
            }

            public function testSafeExecute(callable $handler, string $eventName): void
            {
                $this->safeExecute($handler, $eventName);
            }
        };

        $called = false;
        $handler = function () use (&$called): void {
            $called = true;
        };

        $subscriber->testSafeExecute($handler, 'test.event');

        self::assertTrue($called);
    }

    public function testSafeExecuteLogsExceptionWithAllContextFields(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $exceptionMessage = 'Test exception message';
        $exception = new \RuntimeException($exceptionMessage);

        $logger->expects(self::once())
            ->method('error')
            ->with(
                'Event subscriber execution failed',
                self::callback(static function (array $context) use ($exceptionMessage): bool {
                    // Verify all required context fields are present
                    if (!isset($context['subscriber'])) {
                        return false;
                    }
                    if (!isset($context['event'])) {
                        return false;
                    }
                    if (!isset($context['error'])) {
                        return false;
                    }
                    if (!isset($context['trace'])) {
                        return false;
                    }

                    // Verify field values
                    if ($context['event'] !== 'test.event') {
                        return false;
                    }
                    if ($context['error'] !== $exceptionMessage) {
                        return false;
                    }
                    if (!is_string($context['trace'])) {
                        return false;
                    }

                    return true;
                })
            );

        $subscriber = new class($logger) extends ResilientEventSubscriber {
            public static function getSubscribedEvents(): array
            {
                return [];
            }

            public function testSafeExecute(callable $handler, string $eventName): void
            {
                $this->safeExecute($handler, $eventName);
            }
        };

        $handler = static function () use ($exception): void {
            throw $exception;
        };

        $subscriber->testSafeExecute($handler, 'test.event');
    }

    public function testSafeExecuteDoesNotPropagateException(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error');

        $subscriber = new class($logger) extends ResilientEventSubscriber {
            public static function getSubscribedEvents(): array
            {
                return [];
            }

            public function testSafeExecute(callable $handler, string $eventName): void
            {
                $this->safeExecute($handler, $eventName);
            }
        };

        $handler = static function (): void {
            throw new \RuntimeException('Test exception');
        };

        // Should not throw exception
        $subscriber->testSafeExecute($handler, 'test.event');

        // If we reach here, the exception was not propagated
        self::assertTrue(true);
    }
}
