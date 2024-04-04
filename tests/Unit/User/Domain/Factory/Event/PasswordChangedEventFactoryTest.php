<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Factory\Event;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Event\PasswordChangedEvent;
use App\User\Domain\Factory\Event\PasswordChangedEventFactory;
use App\User\Domain\Factory\Event\PasswordChangedEventFactoryInterface;

final class PasswordChangedEventFactoryTest extends UnitTestCase
{
    private PasswordChangedEventFactoryInterface $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new PasswordChangedEventFactory();
    }

    public function testCreateEvent(): void
    {
        $email = $this->faker->email();
        $eventId = $this->faker->uuid();

        $event = $this->factory->create($email, $eventId);

        $this->assertInstanceOf(PasswordChangedEvent::class, $event);
        $this->assertEquals($email, $event->email);
    }
}
