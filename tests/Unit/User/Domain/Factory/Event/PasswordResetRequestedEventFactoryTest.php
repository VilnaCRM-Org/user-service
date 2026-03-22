<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Factory\Event;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Event\PasswordResetRequestedEvent;
use App\User\Domain\Factory\Event\PasswordResetRequestedEventFactory;

final class PasswordResetRequestedEventFactoryTest extends UnitTestCase
{
    private PasswordResetRequestedEventFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new PasswordResetRequestedEventFactory();
    }

    public function testCreatesPasswordResetRequestedEvent(): void
    {
        $userId = $this->faker->uuid();
        $userEmail = $this->faker->safeEmail();
        $user = $this->createMock(UserInterface::class);
        $user->method('getId')->willReturn($userId);
        $user->method('getEmail')->willReturn($userEmail);
        $token = $this->faker->sha256();
        $eventId = $this->faker->uuid();

        $event = $this->factory->create($user, $token, $eventId);

        $this->assertInstanceOf(PasswordResetRequestedEvent::class, $event);
        $this->assertSame($userId, $event->userId);
        $this->assertSame($userEmail, $event->userEmail);
        $this->assertSame($token, $event->token);
        $this->assertSame($eventId, $event->eventId());
    }
}
