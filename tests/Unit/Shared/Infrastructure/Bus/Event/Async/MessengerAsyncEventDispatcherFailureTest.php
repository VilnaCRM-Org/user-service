<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Event\Async;

use App\Shared\Infrastructure\Bus\Event\Async\MessengerAsyncEventDispatcher;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class MessengerAsyncEventDispatcherFailureTest extends UnitTestCase
{
    private MessageBusInterface&MockObject $messageBus;
    private LoggerInterface&MockObject $logger;
    private MessengerAsyncEventDispatcher $dispatcher;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->dispatcher = new MessengerAsyncEventDispatcher(
            $this->messageBus,
            $this->logger
        );
    }

    public function testDispatchHandlesExceptionAndReturnsFalse(): void
    {
        $event = new TestEvent($this->faker->uuid());
        $exception = new TestMessengerException('Queue unavailable');
        $expectedContext = $this->expectedErrorContext($event, 'Queue unavailable');
        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->willThrowException($exception);
        $this->expectErrorLog($expectedContext);
        $this->logger
            ->expects($this->never())
            ->method('debug');
        self::assertFalse($this->dispatcher->dispatch($event));
    }

    public function testDispatchPartialFailureReturnsFalse(): void
    {
        $event1 = new TestEvent($this->faker->uuid());
        $event2 = new TestEvent($this->faker->uuid());
        $exception = new TestMessengerException('Queue full');
        $envelope = $this->createMock(Envelope::class);

        $this->messageBus
            ->expects($this->exactly(2))
            ->method('dispatch')
            ->willReturnCallback(static function () use ($exception, $envelope) {
                static $callCount = 0;
                $callCount++;
                if ($callCount === 1) {
                    return $envelope;
                }
                throw $exception;
            });

        $this->logger->expects($this->once())->method('debug');
        $this->logger->expects($this->once())->method('error');

        $result = $this->dispatcher->dispatch($event1, $event2);

        self::assertFalse($result);
    }

    /**
     * @return array<string, string>
     */
    private function expectedErrorContext(TestEvent $event, string $message): array
    {
        return [
            'event_id' => $event->eventId(),
            'event_type' => TestEvent::class,
            'event_name' => TestEvent::eventName(),
            'error' => $message,
            'exception_class' => TestMessengerException::class,
        ];
    }

    /**
     * @param array<string, string> $expectedContext
     */
    private function expectErrorLog(array $expectedContext): void
    {
        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'Failed to dispatch domain event to queue (Layer 1)',
                $this->callback(
                    static function (array $context) use ($expectedContext): bool {
                        return $expectedContext === array_intersect_key($context, $expectedContext);
                    }
                )
            );
    }
}
