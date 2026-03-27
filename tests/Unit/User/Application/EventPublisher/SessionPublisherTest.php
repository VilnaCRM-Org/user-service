<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\EventPublisher;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Factory\EventIdFactoryInterface;
use App\User\Domain\Event\AllSessionsRevokedEvent;
use App\User\Domain\Event\SessionRevokedEvent;
use App\User\Domain\Factory\Event\SessionRevocationEventFactoryInterface;
use App\User\Infrastructure\Publisher\SessionPublisher;
use PHPUnit\Framework\MockObject\MockObject;

final class SessionPublisherTest extends UnitTestCase
{
    private EventBusInterface&MockObject $eventBus;
    private EventIdFactoryInterface&MockObject $eventIdFactory;
    private SessionRevocationEventFactoryInterface&MockObject $eventFactory;
    private SessionPublisher $publisher;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->eventIdFactory = $this->createMock(EventIdFactoryInterface::class);
        $this->eventFactory = $this->createMock(SessionRevocationEventFactoryInterface::class);
        $this->publisher = new SessionPublisher(
            $this->eventBus,
            $this->eventIdFactory,
            $this->eventFactory
        );
    }

    public function testPublishSessionRevokedDispatchesEvent(): void
    {
        $userId = $this->faker->uuid();
        $sessionId = $this->faker->uuid();
        $reason = 'user_requested';
        $eventId = $this->faker->uuid();
        $event = $this->createMock(SessionRevokedEvent::class);

        $this->eventIdFactory->expects($this->once())
            ->method('generate')
            ->willReturn($eventId);
        $this->eventFactory->expects($this->once())
            ->method('createSessionRevoked')
            ->with($userId, $sessionId, $reason, $eventId)
            ->willReturn($event);
        $this->eventBus->expects($this->once())
            ->method('publish')
            ->with($event);

        $this->publisher->publishSessionRevoked($userId, $sessionId, $reason);
    }

    public function testPublishAllSessionsRevokedDispatchesEvent(): void
    {
        $userId = $this->faker->uuid();
        $reason = 'password_changed';
        $revokedCount = $this->faker->numberBetween(1, 10);
        $eventId = $this->faker->uuid();
        $event = $this->createMock(AllSessionsRevokedEvent::class);

        $this->eventIdFactory->expects($this->once())
            ->method('generate')
            ->willReturn($eventId);
        $this->eventFactory->expects($this->once())
            ->method('createAllSessionsRevoked')
            ->with($userId, $reason, $revokedCount, $eventId)
            ->willReturn($event);
        $this->eventBus->expects($this->once())
            ->method('publish')
            ->with($event);

        $this->publisher->publishAllSessionsRevoked($userId, $reason, $revokedCount);
    }
}
