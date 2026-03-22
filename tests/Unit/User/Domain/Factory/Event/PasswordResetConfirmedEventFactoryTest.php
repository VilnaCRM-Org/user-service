<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Factory\Event;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Event\PasswordResetConfirmedEvent;
use App\User\Domain\Factory\Event\PasswordResetConfirmedEventFactory;

final class PasswordResetConfirmedEventFactoryTest extends UnitTestCase
{
    private PasswordResetConfirmedEventFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new PasswordResetConfirmedEventFactory();
    }

    public function testCreatesPasswordResetConfirmedEvent(): void
    {
        $userId = $this->faker->uuid();
        $eventId = $this->faker->uuid();

        $event = $this->factory->create($userId, $eventId);

        $this->assertInstanceOf(PasswordResetConfirmedEvent::class, $event);
        $this->assertSame($userId, $event->userId);
        $this->assertSame($eventId, $event->eventId());
    }
}
