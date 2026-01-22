<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus;

use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use App\Shared\Infrastructure\Bus\CallableFirstParameterExtractor;
use App\Shared\Infrastructure\Bus\InvokeParameterExtractor;
use App\Shared\Infrastructure\Bus\MessageBusFactory;
use App\Tests\Unit\Shared\Infrastructure\Bus\Stub\TestCommand;
use App\Tests\Unit\Shared\Infrastructure\Bus\Stub\TestEvent;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Messenger\MessageBus;

final class MessageBusFactoryTest extends UnitTestCase
{
    public function testCreate(): void
    {
        $commandHandlers = [];

        $factory = new MessageBusFactory(new CallableFirstParameterExtractor(new InvokeParameterExtractor()));

        $messageBus = $factory->create($commandHandlers);

        $this->assertInstanceOf(MessageBus::class, $messageBus);
    }

    public function testCreateWithMixedHandlers(): void
    {
        $regularHandlerCalled = false;
        $subscriberCalled = false;

        $regularHandler = new class($regularHandlerCalled) {
            public function __construct(private bool &$called)
            {
            }

            public function __invoke(TestCommand $command): void
            {
                $this->called = true;
            }
        };

        $subscriber = new class($subscriberCalled) implements DomainEventSubscriberInterface {
            public function __construct(private bool &$called)
            {
            }

            /**
             * @return array<class-string>
             */
            #[\Override]
            public function subscribedTo(): array
            {
                return [TestEvent::class];
            }

            public function __invoke(TestEvent $event): void
            {
                $this->called = true;
            }
        };

        $handlers = [$regularHandler, $subscriber];

        $factory = new MessageBusFactory(new CallableFirstParameterExtractor(new InvokeParameterExtractor()));

        $messageBus = $factory->create($handlers);

        // Dispatch command to regular handler
        $messageBus->dispatch(new TestCommand());
        self::assertTrue($regularHandlerCalled, 'Regular handler should be called');

        // Dispatch event to subscriber
        $messageBus->dispatch(new TestEvent('event-id', '2024-01-01 00:00:00'));
        self::assertTrue($subscriberCalled, 'Subscriber should be called');
    }

    public function testCreateWithOnlySubscribers(): void
    {
        $subscriber1Called = false;
        $subscriber2Called = false;

        $subscriber1 = new class($subscriber1Called) implements DomainEventSubscriberInterface {
            public function __construct(private bool &$called)
            {
            }

            #[\Override]
            public function subscribedTo(): array
            {
                return [TestEvent::class];
            }

            public function __invoke(TestEvent $event): void
            {
                $this->called = true;
            }
        };

        $subscriber2 = new class($subscriber2Called) implements DomainEventSubscriberInterface {
            public function __construct(private bool &$called)
            {
            }

            #[\Override]
            public function subscribedTo(): array
            {
                return [TestEvent::class];
            }

            public function __invoke(TestEvent $event): void
            {
                $this->called = true;
            }
        };

        $handlers = [$subscriber1, $subscriber2];

        $factory = new MessageBusFactory(new CallableFirstParameterExtractor(new InvokeParameterExtractor()));

        $messageBus = $factory->create($handlers);

        // Dispatch event should call both subscribers
        $messageBus->dispatch(new TestEvent('event-id', '2024-01-01 00:00:00'));
        self::assertTrue($subscriber1Called, 'Subscriber 1 should be called');
        self::assertTrue($subscriber2Called, 'Subscriber 2 should be called');
    }

    public function testCreateWithOnlyRegularHandlers(): void
    {
        $handler1Called = false;

        $handler1 = new class($handler1Called) {
            public function __construct(private bool &$called)
            {
            }

            public function __invoke(TestCommand $command): void
            {
                $this->called = true;
            }
        };

        $handlers = [$handler1];

        $factory = new MessageBusFactory(new CallableFirstParameterExtractor(new InvokeParameterExtractor()));

        $messageBus = $factory->create($handlers);

        // Dispatch command to regular handler
        $messageBus->dispatch(new TestCommand());
        self::assertTrue($handler1Called, 'Regular handler should be called');
    }

    public function testSubscriberHandlesMultipleEventTypes(): void
    {
        $event1Called = false;
        $event2Called = false;

        $subscriber = new class($event1Called, $event2Called) implements DomainEventSubscriberInterface {
            public function __construct(
                private bool &$event1Called,
                private bool &$event2Called
            ) {
            }

            #[\Override]
            public function subscribedTo(): array
            {
                return [TestEvent::class, TestCommand::class];
            }

            public function __invoke(TestEvent|TestCommand $message): void
            {
                if ($message instanceof TestEvent) {
                    $this->event1Called = true;
                } elseif ($message instanceof TestCommand) {
                    $this->event2Called = true;
                }
            }
        };

        $factory = new MessageBusFactory(new CallableFirstParameterExtractor(new InvokeParameterExtractor()));
        $messageBus = $factory->create([$subscriber]);

        // Subscriber should handle both event types
        $messageBus->dispatch(new TestEvent('event-id', '2024-01-01 00:00:00'));
        self::assertTrue($event1Called, 'Subscriber should handle TestEvent');

        $messageBus->dispatch(new TestCommand());
        self::assertTrue($event2Called, 'Subscriber should handle TestCommand');
    }

    public function testSubscriberRegisteredOnceInHandlersMap(): void
    {
        $subscriber = new class() implements DomainEventSubscriberInterface {
            #[\Override]
            public function subscribedTo(): array
            {
                return [TestEvent::class];
            }

            public function __invoke(TestEvent $event): void
            {
            }
        };

        $factory = new MessageBusFactory(
            new CallableFirstParameterExtractor(new InvokeParameterExtractor())
        );

        $reflection = new \ReflectionMethod(MessageBusFactory::class, 'buildHandlersMap');
        $this->makeAccessible($reflection);

        /** @var array<string, array<DomainEventSubscriberInterface>> $handlersMap */
        $handlersMap = $reflection->invoke($factory, [$subscriber]);

        self::assertArrayHasKey(TestEvent::class, $handlersMap);
        self::assertCount(1, $handlersMap[TestEvent::class]);
        self::assertSame($subscriber, $handlersMap[TestEvent::class][0]);
    }
}
