<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus;

use App\Shared\Infrastructure\Bus\MessageBusFactory;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Messenger\MessageBus;

class MessageBusFactoryTest extends UnitTestCase
{
    public function testCreate(): void
    {
        $commandHandlers = [];

        $factory = new MessageBusFactory();

        $messageBus = $factory->create($commandHandlers);

        $this->assertInstanceOf(MessageBus::class, $messageBus);
    }
}
