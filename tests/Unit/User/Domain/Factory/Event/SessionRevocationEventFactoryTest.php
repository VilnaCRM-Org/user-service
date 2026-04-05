<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Factory\Event;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Factory\Event\SessionRevocationEventFactory;
use App\User\Domain\Factory\Event\SessionRevocationEventFactoryInterface;

final class SessionRevocationEventFactoryTest extends UnitTestCase
{
    private SessionRevocationEventFactoryInterface $factory;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new SessionRevocationEventFactory();
    }

    public function testCreateSessionRevoked(): void
    {
        $userId = $this->faker->uuid();
        $sessionId = $this->faker->uuid();
        $reason = 'user_requested';

        $event = $this->factory->createSessionRevoked(
            $userId,
            $sessionId,
            $reason,
            $this->faker->uuid()
        );

        $this->assertSame($userId, $event->userId);
        $this->assertSame($sessionId, $event->sessionId);
        $this->assertSame($reason, $event->reason);
    }

    public function testCreateAllSessionsRevoked(): void
    {
        $userId = $this->faker->uuid();
        $reason = 'password_change';
        $revokedCount = $this->faker->numberBetween(1, 5);

        $event = $this->factory->createAllSessionsRevoked(
            $userId,
            $reason,
            $revokedCount,
            $this->faker->uuid()
        );

        $this->assertSame($userId, $event->userId);
        $this->assertSame($reason, $event->reason);
        $this->assertSame($revokedCount, $event->revokedCount);
    }
}
