<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Domain\Factory\Event;

use App\OAuth\Domain\Event\OAuthUserCreatedEvent;
use App\OAuth\Domain\Event\OAuthUserSignedInEvent;
use App\OAuth\Domain\Factory\Event\OAuthEventFactory;
use App\Tests\Unit\UnitTestCase;

final class OAuthEventFactoryTest extends UnitTestCase
{
    private OAuthEventFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new OAuthEventFactory();
    }

    public function testCreateUserCreatedReturnsEvent(): void
    {
        $userId = $this->faker->uuid();
        $email = $this->faker->safeEmail();
        $provider = $this->faker->word();
        $eventId = $this->faker->uuid();

        $event = $this->factory->createUserCreated(
            $userId,
            $email,
            $provider,
            $eventId
        );

        $this->assertInstanceOf(OAuthUserCreatedEvent::class, $event);
        $this->assertSame($userId, $event->userId);
        $this->assertSame($email, $event->email);
        $this->assertSame($provider, $event->provider);
        $this->assertSame($eventId, $event->eventId());
    }

    public function testCreateUserSignedInReturnsEvent(): void
    {
        $userId = $this->faker->uuid();
        $email = $this->faker->safeEmail();
        $provider = $this->faker->word();
        $sessionId = $this->faker->uuid();
        $eventId = $this->faker->uuid();

        $event = $this->factory->createUserSignedIn(
            $userId,
            $email,
            $provider,
            $sessionId,
            $eventId
        );

        $this->assertInstanceOf(OAuthUserSignedInEvent::class, $event);
        $this->assertSame($userId, $event->userId);
        $this->assertSame($email, $event->email);
        $this->assertSame($provider, $event->provider);
        $this->assertSame($sessionId, $event->sessionId);
        $this->assertSame($eventId, $event->eventId());
    }
}
