<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Factory\Event;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Factory\Event\TwoFactorEventFactory;
use App\User\Domain\Factory\Event\TwoFactorEventFactoryInterface;

final class TwoFactorEventFactoryTest extends UnitTestCase
{
    private TwoFactorEventFactoryInterface $factory;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new TwoFactorEventFactory();
    }

    public function testCreateEnabled(): void
    {
        $userId = $this->faker->uuid();
        $email = $this->faker->email();

        $event = $this->factory->createEnabled($userId, $email, $this->faker->uuid());

        $this->assertSame($userId, $event->userId);
        $this->assertSame($email, $event->email);
    }

    public function testCreateDisabled(): void
    {
        $userId = $this->faker->uuid();
        $email = $this->faker->email();

        $event = $this->factory->createDisabled($userId, $email, $this->faker->uuid());

        $this->assertSame($userId, $event->userId);
        $this->assertSame($email, $event->email);
    }

    public function testCreateCompleted(): void
    {
        $userId = $this->faker->uuid();
        $sessionId = $this->faker->uuid();
        $method = 'totp';

        $event = $this->factory->createCompleted(
            $userId,
            $sessionId,
            $this->faker->ipv4(),
            $this->faker->userAgent(),
            $method,
            $this->faker->uuid()
        );

        $this->assertSame($userId, $event->userId);
        $this->assertSame($sessionId, $event->sessionId);
        $this->assertSame($method, $event->method);
    }

    public function testCreateFailed(): void
    {
        $pendingSessionId = $this->faker->uuid();
        $reason = $this->faker->sentence();

        $event = $this->factory->createFailed(
            $pendingSessionId,
            $this->faker->ipv4(),
            $reason,
            $this->faker->uuid()
        );

        $this->assertSame($pendingSessionId, $event->pendingSessionId);
        $this->assertSame($reason, $event->reason);
    }

    public function testCreateRecoveryCodeUsed(): void
    {
        $userId = $this->faker->uuid();
        $remainingCount = $this->faker->numberBetween(0, 8);

        $event = $this->factory->createRecoveryCodeUsed(
            $userId,
            $remainingCount,
            $this->faker->uuid()
        );

        $this->assertSame($userId, $event->userId);
        $this->assertSame($remainingCount, $event->remainingCount);
    }
}
