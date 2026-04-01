<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Domain\Event;

use App\OAuth\Domain\Event\OAuthDomainEvent;
use App\OAuth\Domain\Event\OAuthUserSignedInEvent;
use App\Tests\Unit\UnitTestCase;

final class OAuthUserSignedInEventTest extends UnitTestCase
{
    public function testConstructSetsProperties(): void
    {
        $userId = $this->faker->uuid();
        $email = $this->faker->safeEmail();
        $provider = $this->faker->word();
        $sessionId = $this->faker->uuid();
        $eventId = $this->faker->uuid();

        $event = new OAuthUserSignedInEvent(
            $userId,
            $email,
            $provider,
            $sessionId,
            $eventId
        );

        $this->assertSame($userId, $event->userId);
        $this->assertSame($email, $event->email);
        $this->assertSame($provider, $event->provider);
        $this->assertSame($sessionId, $event->sessionId);
        $this->assertSame($eventId, $event->eventId());
        $this->assertInstanceOf(OAuthDomainEvent::class, $event);
    }

    public function testEventNameReturnsExpectedValue(): void
    {
        $this->assertSame('oauth.user_signed_in', OAuthUserSignedInEvent::eventName());
    }

    public function testToPrimitivesReturnsExpectedArray(): void
    {
        $userId = $this->faker->uuid();
        $email = $this->faker->safeEmail();
        $provider = $this->faker->word();
        $sessionId = $this->faker->uuid();

        $event = new OAuthUserSignedInEvent(
            $userId,
            $email,
            $provider,
            $sessionId,
            $this->faker->uuid()
        );

        $this->assertSame([
            'userId' => $userId,
            'email' => $email,
            'provider' => $provider,
            'sessionId' => $sessionId,
        ], $event->toPrimitives());
    }

    public function testFromPrimitivesCreatesEvent(): void
    {
        $userId = $this->faker->uuid();
        $email = $this->faker->safeEmail();
        $provider = $this->faker->word();
        $sessionId = $this->faker->uuid();

        $event = OAuthUserSignedInEvent::fromPrimitives(
            $this->buildPrimitives($userId, $email, $provider, $sessionId),
            $this->faker->uuid(),
            $this->faker->dateTimeThisYear()->format('Y-m-d H:i:s')
        );

        $this->assertInstanceOf(OAuthUserSignedInEvent::class, $event);
        $this->assertSame($userId, $event->userId);
        $this->assertSame($sessionId, $event->sessionId);
    }

    /**
     * @return array{userId: string, email: string, provider: string, sessionId: string}
     */
    private function buildPrimitives(
        string $userId,
        string $email,
        string $provider,
        string $sessionId,
    ): array {
        return [
            'userId' => $userId,
            'email' => $email,
            'provider' => $provider,
            'sessionId' => $sessionId,
        ];
    }
}
