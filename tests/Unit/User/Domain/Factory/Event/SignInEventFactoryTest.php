<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Factory\Event;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Factory\Event\SignInEventFactory;
use App\User\Domain\Factory\Event\SignInEventFactoryInterface;

final class SignInEventFactoryTest extends UnitTestCase
{
    private SignInEventFactoryInterface $factory;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new SignInEventFactory();
    }

    public function testCreateSignedIn(): void
    {
        $eventId = $this->faker->uuid();

        $event = $this->factory->createSignedIn(
            $this->faker->uuid(),
            $this->faker->email(),
            $this->faker->uuid(),
            $this->faker->ipv4(),
            $this->faker->userAgent(),
            true,
            $eventId
        );

        $this->assertSame($eventId, $event->eventId());
        $this->assertTrue($event->twoFactorUsed);
    }

    public function testCreateFailed(): void
    {
        $email = $this->faker->email();
        $reason = $this->faker->sentence();

        $event = $this->factory->createFailed(
            $email,
            $this->faker->ipv4(),
            $this->faker->userAgent(),
            $reason,
            $this->faker->uuid()
        );

        $this->assertSame($email, $event->email);
        $this->assertSame($reason, $event->reason);
    }

    public function testCreateLockedOut(): void
    {
        $failedAttempts = $this->faker->numberBetween(3, 10);
        $lockoutDurationSeconds = $this->faker->numberBetween(60, 3600);

        $event = $this->factory->createLockedOut(
            $this->faker->email(),
            $failedAttempts,
            $lockoutDurationSeconds,
            $this->faker->uuid()
        );

        $this->assertSame($failedAttempts, $event->failedAttempts);
        $this->assertSame($lockoutDurationSeconds, $event->lockoutDurationSeconds);
    }
}
