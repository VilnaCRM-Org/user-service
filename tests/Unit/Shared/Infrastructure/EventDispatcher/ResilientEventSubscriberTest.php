<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\EventDispatcher;

use App\Tests\Unit\Shared\Infrastructure\EventDispatcher\Stub\TestResilientEventSubscriber;
use App\Tests\Unit\UnitTestCase;
use Psr\Log\LoggerInterface;

final class ResilientEventSubscriberTest extends UnitTestCase
{
    public function testSafeExecuteCallsHandlerSuccessfully(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('error');

        $subscriber = $this->createTestSubscriber($logger);
        $called = false;
        $handler = $this->createSuccessHandler($called);

        $subscriber->testSafeExecute($handler, 'test.event');

        self::assertTrue($called);
    }

    public function testSafeExecuteLogsExceptionWithAllContextFields(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $exceptionMessage = 'Test exception message';
        $exception = new \RuntimeException($exceptionMessage);

        $this->expectLoggerErrorWithContext($logger, $exceptionMessage);

        $subscriber = $this->createTestSubscriber($logger);
        $handler = $this->createThrowingHandler($exception);

        $subscriber->testSafeExecute($handler, 'test.event');
    }

    public function testSafeExecuteDoesNotPropagateException(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error');

        $subscriber = $this->createTestSubscriber($logger);
        $handler = $this->createThrowingHandler(new \RuntimeException('Test exception'));

        $subscriber->testSafeExecute($handler, 'test.event');

        self::assertTrue(true);
    }

    /**
     * @psalm-return \Closure():void
     */
    private function createSuccessHandler(bool &$called): \Closure
    {
        return static function () use (&$called): void {
            $called = true;
        };
    }

    private function expectLoggerErrorWithContext(
        LoggerInterface $logger,
        string $exceptionMessage
    ): void {
        $logger->expects(self::once())
            ->method('error')
            ->with(
                'Event subscriber execution failed',
                self::callback($this->createContextValidator($exceptionMessage))
            );
    }

    /**
     * @psalm-return \Closure():never
     */
    private function createThrowingHandler(\RuntimeException $exception): \Closure
    {
        return /**
         * @return never
         */
        static function () use ($exception) {
            throw $exception;
        };
    }

    private function createTestSubscriber(LoggerInterface $logger): TestResilientEventSubscriber
    {
        return new TestResilientEventSubscriber($logger);
    }

    /**
     * @psalm-return \Closure(array):bool
     */
    private function createContextValidator(string $exceptionMessage): \Closure
    {
        $test = $this;
        return static function (array $context) use ($exceptionMessage, $test): bool {
            return $test->validateContext($context, $exceptionMessage);
        };
    }

    /** @param array<string, string|object> $context */
    private function validateContext(array $context, string $exceptionMessage): bool
    {
        if (!$this->hasRequiredFields($context)) {
            return false;
        }

        return $this->hasCorrectValues($context, $exceptionMessage);
    }

    /** @param array<string, string|object> $context */
    private function hasRequiredFields(array $context): bool
    {
        return isset(
            $context['subscriber'],
            $context['event'],
            $context['error'],
            $context['trace']
        );
    }

    /** @param array<string, string|object> $context */
    private function hasCorrectValues(array $context, string $exceptionMessage): bool
    {
        return $context['event'] === 'test.event'
            && $context['error'] === $exceptionMessage
            && is_string($context['trace']);
    }
}
