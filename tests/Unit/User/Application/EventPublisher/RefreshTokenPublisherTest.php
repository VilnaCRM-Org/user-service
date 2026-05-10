<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\EventPublisher;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Factory\EventIdFactoryInterface;
use App\User\Domain\Event\RefreshTokenRotatedEvent;
use App\User\Domain\Event\RefreshTokenTheftDetectedEvent;
use App\User\Domain\Factory\Event\RefreshTokenEventFactoryInterface;
use App\User\Infrastructure\Publisher\RefreshTokenPublisher;
use PHPUnit\Framework\MockObject\MockObject;

final class RefreshTokenPublisherTest extends UnitTestCase
{
    private EventBusInterface&MockObject $eventBus;
    private EventIdFactoryInterface&MockObject $eventIdFactory;
    private RefreshTokenEventFactoryInterface&MockObject $eventFactory;
    private RefreshTokenPublisher $publisher;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->eventIdFactory = $this->createMock(EventIdFactoryInterface::class);
        $this->eventFactory = $this->createMock(RefreshTokenEventFactoryInterface::class);
        $this->publisher = new RefreshTokenPublisher(
            $this->eventBus,
            $this->eventIdFactory,
            $this->eventFactory
        );
    }

    public function testPublishTokenRotated(): void
    {
        $sessionId = $this->faker->uuid();
        $userId = $this->faker->uuid();
        $eventId = $this->faker->uuid();
        $event = $this->createMock(RefreshTokenRotatedEvent::class);

        $this->expectGeneratedEventId($eventId);
        $this->eventFactory->expects($this->once())
            ->method('createRotated')
            ->with($sessionId, $userId, $eventId)
            ->willReturn($event);
        $this->expectPublishedEvent($event);

        $this->publisher->publishTokenRotated($sessionId, $userId);
    }

    public function testPublishTheftDetected(): void
    {
        $sessionId = $this->faker->uuid();
        $userId = $this->faker->uuid();
        $ipAddress = $this->faker->ipv4();
        $reason = 'grace_period_expired';
        $eventId = $this->faker->uuid();
        $event = $this->createMock(RefreshTokenTheftDetectedEvent::class);

        $this->expectGeneratedEventId($eventId);
        $this->eventFactory->expects($this->once())
            ->method('createTheftDetected')
            ->with($sessionId, $userId, $ipAddress, $reason, $eventId)
            ->willReturn($event);
        $this->expectPublishedEvent($event);

        $this->publisher->publishTheftDetected($sessionId, $userId, $ipAddress, $reason);
    }

    private function expectGeneratedEventId(string $eventId): void
    {
        $this->eventIdFactory->expects($this->once())
            ->method('generate')
            ->willReturn($eventId);
    }

    private function expectPublishedEvent(object $event): void
    {
        $this->eventBus->expects($this->once())
            ->method('publish')
            ->with($event);
    }
}
