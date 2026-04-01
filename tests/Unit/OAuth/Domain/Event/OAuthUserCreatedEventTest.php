<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Domain\Event;

use App\OAuth\Domain\Event\OAuthDomainEvent;
use App\OAuth\Domain\Event\OAuthUserCreatedEvent;
use App\Tests\Unit\UnitTestCase;

final class OAuthUserCreatedEventTest extends UnitTestCase
{
    public function testConstructSetsProperties(): void
    {
        $userId = $this->faker->uuid();
        $email = $this->faker->safeEmail();
        $provider = $this->faker->word();
        $eventId = $this->faker->uuid();

        $event = new OAuthUserCreatedEvent(
            $userId,
            $email,
            $provider,
            $eventId
        );

        $this->assertSame($userId, $event->userId);
        $this->assertSame($email, $event->email);
        $this->assertSame($provider, $event->provider);
        $this->assertSame($eventId, $event->eventId());
        $this->assertInstanceOf(OAuthDomainEvent::class, $event);
    }

    public function testEventNameReturnsExpectedValue(): void
    {
        $this->assertSame('oauth.user_created', OAuthUserCreatedEvent::eventName());
    }

    public function testToPrimitivesReturnsExpectedArray(): void
    {
        $userId = $this->faker->uuid();
        $email = $this->faker->safeEmail();
        $provider = $this->faker->word();

        $event = new OAuthUserCreatedEvent(
            $userId,
            $email,
            $provider,
            $this->faker->uuid()
        );

        $this->assertSame([
            'userId' => $userId,
            'email' => $email,
            'provider' => $provider,
        ], $event->toPrimitives());
    }

    public function testFromPrimitivesCreatesEvent(): void
    {
        $userId = $this->faker->uuid();
        $email = $this->faker->safeEmail();
        $provider = $this->faker->word();
        $eventId = $this->faker->uuid();
        $occurredOn = $this->faker->dateTimeThisYear()->format('Y-m-d H:i:s');

        $event = OAuthUserCreatedEvent::fromPrimitives(
            ['userId' => $userId, 'email' => $email, 'provider' => $provider],
            $eventId,
            $occurredOn
        );

        $this->assertInstanceOf(OAuthUserCreatedEvent::class, $event);
        $this->assertSame($userId, $event->userId);
        $this->assertSame($email, $event->email);
        $this->assertSame($provider, $event->provider);
        $this->assertSame($eventId, $event->eventId());
    }
}
