<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Event;

use App\Shared\Domain\ValueObject\Uuid;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Event\PasswordResetRequestedEvent;
use App\User\Domain\Factory\UserFactory;

final class PasswordResetRequestedEventTest extends UnitTestCase
{
    public function testConstruction(): void
    {
        $userFactory = new UserFactory();
        $user = $userFactory->create(
            $this->faker->safeEmail(),
            $this->faker->lexify('??'),
            $this->faker->password(),
            new Uuid($this->faker->uuid())
        );
        $token = $this->faker->sha256();
        $eventId = $this->faker->uuid();
        $event = new PasswordResetRequestedEvent($user, $token, $eventId);

        $this->assertSame($user, $event->user);
        $this->assertSame($token, $event->token);
        $this->assertSame($eventId, $event->eventId());
    }

    public function testEventName(): void
    {
        $eventName = PasswordResetRequestedEvent::eventName();

        $this->assertSame('user.password_reset_requested', $eventName);
    }

    public function testToPrimitives(): void
    {
        $userFactory = new UserFactory();
        $uuid = new Uuid($this->faker->uuid());
        $user = $userFactory->create(
            $this->faker->safeEmail(),
            $this->faker->lexify('??'),
            $this->faker->password(),
            $uuid
        );
        $token = $this->faker->sha256();
        $eventId = $this->faker->uuid();
        $event = new PasswordResetRequestedEvent($user, $token, $eventId);
        $primitives = $event->toPrimitives();

        $this->assertIsArray($primitives);
        $this->assertArrayHasKey('userId', $primitives);
        $this->assertArrayHasKey('userEmail', $primitives);
        $this->assertArrayHasKey('token', $primitives);
        $this->assertSame($user->getId(), $primitives['userId']);
        $this->assertSame($user->getEmail(), $primitives['userEmail']);
        $this->assertSame($token, $primitives['token']);
    }

    public function testFromPrimitivesThrowsException(): void
    {
        $body = [
            'userId' => $this->faker->uuid(),
            'userEmail' => $this->faker->safeEmail(),
            'token' => $this->faker->sha256(),
        ];
        $eventId = $this->faker->uuid();
        $occurredOn = $this->faker->dateTime()->format('Y-m-d H:i:s');

        $this->expectException(\RuntimeException::class);

        PasswordResetRequestedEvent::fromPrimitives($body, $eventId, $occurredOn);
    }
}
