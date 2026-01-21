<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus;

use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use App\Shared\Infrastructure\Bus\CallableFirstParameterExtractor;
use App\Shared\Infrastructure\Bus\MessageBusFactory;
use App\Tests\Unit\Shared\Infrastructure\Bus\Stub\TestCommand;
use App\Tests\Unit\Shared\Infrastructure\Bus\Stub\TestEvent;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;

final class MessageBusFactoryTest extends UnitTestCase
{
    public function testCreate(): void
    {
        $commandHandlers = [];

        $extractor = new CallableFirstParameterExtractor();
        $factory = new MessageBusFactory($extractor);

        $messageBus = $factory->create($commandHandlers);
        $expectedMessageBus = new MessageBus(
            [
                new HandleMessageMiddleware(
                    new HandlersLocator(
                        $extractor->forCallables($commandHandlers)
                    )
                ),
            ]
        );

        $this->assertInstanceOf(MessageBus::class, $messageBus);
        $this->assertEquals($expectedMessageBus, $messageBus);
    }

    public function testCreateWithMixedHandlers(): void
    {
        $regularHandler = new class() {
            public function __invoke(TestCommand $command): void
            {
            }
        };

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

        $handlers = [$regularHandler, $subscriber];

        $factory = new MessageBusFactory(new CallableFirstParameterExtractor());

        $messageBus = $factory->create($handlers);

        $this->assertInstanceOf(MessageBus::class, $messageBus);
    }

    public function testCreateWithOnlySubscribers(): void
    {
        $subscriber1 = new class() implements DomainEventSubscriberInterface {
            /**
             * @return array<string>
             */
            #[\Override]
            public function subscribedTo(): array
            {
                return [TestEvent::class];
            }

            public function __invoke(TestEvent $event): void
            {
            }
        };

        $subscriber2 = new class() implements DomainEventSubscriberInterface {
            /**
             * @return array<string>
             */
            #[\Override]
            public function subscribedTo(): array
            {
                return [TestEvent::class];
            }

            public function __invoke(TestEvent $event): void
            {
            }
        };

        $handlers = [$subscriber1, $subscriber2];

        $factory = new MessageBusFactory(new CallableFirstParameterExtractor());

        $messageBus = $factory->create($handlers);

        $this->assertInstanceOf(MessageBus::class, $messageBus);
    }

    public function testCreateWithOnlyRegularHandlers(): void
    {
        $handler1 = new class() {
            public function __invoke(TestCommand $command): void
            {
            }
        };

        $handler2 = new class() {
            public function __invoke(TestCommand $command): void
            {
            }
        };

        $handlers = [$handler1, $handler2];

        $factory = new MessageBusFactory(new CallableFirstParameterExtractor());

        $messageBus = $factory->create($handlers);

        $this->assertInstanceOf(MessageBus::class, $messageBus);
    }
}
