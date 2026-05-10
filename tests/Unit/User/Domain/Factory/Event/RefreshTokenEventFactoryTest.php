<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Factory\Event;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Factory\Event\RefreshTokenEventFactory;
use App\User\Domain\Factory\Event\RefreshTokenEventFactoryInterface;

final class RefreshTokenEventFactoryTest extends UnitTestCase
{
    private RefreshTokenEventFactoryInterface $factory;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new RefreshTokenEventFactory();
    }

    public function testCreateRotated(): void
    {
        $sessionId = $this->faker->uuid();
        $userId = $this->faker->uuid();

        $event = $this->factory->createRotated($sessionId, $userId, $this->faker->uuid());

        $this->assertSame($sessionId, $event->sessionId);
        $this->assertSame($userId, $event->userId);
    }

    public function testCreateTheftDetected(): void
    {
        $sessionId = $this->faker->uuid();
        $userId = $this->faker->uuid();
        $ipAddress = $this->faker->ipv4();
        $reason = 'grace_period_expired';

        $event = $this->factory->createTheftDetected(
            $sessionId,
            $userId,
            $ipAddress,
            $reason,
            $this->faker->uuid()
        );

        $this->assertSame($sessionId, $event->sessionId);
        $this->assertSame($userId, $event->userId);
        $this->assertSame($ipAddress, $event->ipAddress);
        $this->assertSame($reason, $event->reason);
    }
}
