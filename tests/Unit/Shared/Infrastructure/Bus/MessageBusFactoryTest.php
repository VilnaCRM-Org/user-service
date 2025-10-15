<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus;

use App\Shared\Infrastructure\Bus\CallableFirstParameterExtractor;
use App\Shared\Infrastructure\Bus\MessageBusFactory;
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
}
