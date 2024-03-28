<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Factory\Event;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Event\UserConfirmedEvent;
use App\User\Domain\Factory\ConfirmationTokenFactory;
use App\User\Domain\Factory\ConfirmationTokenFactoryInterface;
use App\User\Domain\Factory\Event\UserConfirmedEventFactory;
use App\User\Domain\Factory\Event\UserConfirmedEventFactoryInterface;

class UserConfirmedEventFactoryTest extends UnitTestCase
{
    private UserConfirmedEventFactoryInterface $factory;
    private ConfirmationTokenFactoryInterface $confirmationTokenFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new UserConfirmedEventFactory();
        $this->confirmationTokenFactory = new ConfirmationTokenFactory($this->faker->numberBetween(1, 10));
    }

    public function testCreateEvent(): void
    {
        $token = $this->confirmationTokenFactory->create($this->faker->uuid());
        $eventId = $this->faker->uuid();

        $event = $this->factory->create($token, $eventId);

        $this->assertInstanceOf(UserConfirmedEvent::class, $event);
        $this->assertEquals($token, $event->token);
    }
}
