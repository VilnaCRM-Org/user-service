<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\EventPublisher;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Factory\EventIdFactoryInterface;
use App\User\Domain\Event\RefreshTokenRotatedEvent;
use App\User\Domain\Event\RefreshTokenTheftDetectedEvent;
use App\User\Infrastructure\Publisher\RefreshTokenPublisher;
use PHPUnit\Framework\MockObject\MockObject;

final class RefreshTokenPublisherTest extends UnitTestCase
{
    private EventBusInterface&MockObject $eventBus;
    private EventIdFactoryInterface&MockObject $eventIdFactory;
    private RefreshTokenPublisher $publisher;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->eventIdFactory = $this->createMock(EventIdFactoryInterface::class);
        $this->publisher = new RefreshTokenPublisher(
            $this->eventBus,
            $this->eventIdFactory
        );
    }

    public function testPublishTokenRotated(): void
    {
        $sessionId = $this->faker->uuid();
        $userId = $this->faker->uuid();
        $eventId = $this->faker->uuid();

        $this->eventIdFactory->expects($this->once())
            ->method('generate')
            ->willReturn($eventId);

        $this->eventBus->expects($this->once())
            ->method('publish')
            ->with($this->callback(
                static fn (RefreshTokenRotatedEvent $event): bool => $event->sessionId === $sessionId
                    && $event->userId === $userId
                    && $event->eventId() === $eventId
            ));

        $this->publisher->publishTokenRotated($sessionId, $userId);
    }

    public function testPublishTheftDetected(): void
    {
        $sessionId = $this->faker->uuid();
        $userId = $this->faker->uuid();
        $ipAddress = $this->faker->ipv4();
        $reason = 'grace_period_expired';
        $eventId = $this->faker->uuid();

        $this->eventIdFactory->expects($this->once())
            ->method('generate')
            ->willReturn($eventId);

        $this->eventBus->expects($this->once())
            ->method('publish')
            ->with($this->callback(
                static fn (RefreshTokenTheftDetectedEvent $event): bool => $event->sessionId === $sessionId
                    && $event->userId === $userId
                    && $event->ipAddress === $ipAddress
                    && $event->reason === $reason
                    && $event->eventId() === $eventId
            ));

        $this->publisher->publishTheftDetected($sessionId, $userId, $ipAddress, $reason);
    }
}
