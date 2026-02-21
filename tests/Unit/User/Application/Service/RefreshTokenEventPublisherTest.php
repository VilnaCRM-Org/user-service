<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Service;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Factory\AuthTokenFactoryInterface;
use App\User\Application\Service\RefreshTokenEventPublisher;
use App\User\Domain\Event\RefreshTokenRotatedEvent;
use App\User\Domain\Event\RefreshTokenTheftDetectedEvent;
use PHPUnit\Framework\MockObject\MockObject;

final class RefreshTokenEventPublisherTest extends UnitTestCase
{
    private EventBusInterface&MockObject $eventBus;
    private AuthTokenFactoryInterface&MockObject $authTokenFactory;
    private RefreshTokenEventPublisher $publisher;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->authTokenFactory = $this->createMock(AuthTokenFactoryInterface::class);
        $this->publisher = new RefreshTokenEventPublisher(
            $this->eventBus,
            $this->authTokenFactory
        );
    }

    public function testPublishRotated(): void
    {
        $sessionId = $this->faker->uuid();
        $userId = $this->faker->uuid();
        $eventId = $this->faker->uuid();

        $this->authTokenFactory->expects($this->once())
            ->method('nextEventId')
            ->willReturn($eventId);

        $this->eventBus->expects($this->once())
            ->method('publish')
            ->with($this->callback(
                static function (RefreshTokenRotatedEvent $event) use (
                    $sessionId,
                    $userId,
                    $eventId
                ): bool {
                    return $event->sessionId === $sessionId
                        && $event->userId === $userId
                        && $event->eventId() === $eventId;
                }
            ));

        $this->publisher->publishRotated($sessionId, $userId);
    }

    public function testPublishTheftDetected(): void
    {
        $sessionId = $this->faker->uuid();
        $userId = $this->faker->uuid();
        $ipAddress = $this->faker->ipv4();
        $reason = $this->faker->sentence();
        $eventId = $this->faker->uuid();

        $this->authTokenFactory->expects($this->once())
            ->method('nextEventId')
            ->willReturn($eventId);

        $this->eventBus->expects($this->once())
            ->method('publish')
            ->with($this->callback(
                static function (RefreshTokenTheftDetectedEvent $event) use (
                    $sessionId,
                    $userId,
                    $ipAddress,
                    $reason,
                    $eventId
                ): bool {
                    return $event->sessionId === $sessionId
                        && $event->userId === $userId
                        && $event->ipAddress === $ipAddress
                        && $event->reason === $reason
                        && $event->eventId() === $eventId;
                }
            ));

        $this->publisher->publishTheftDetected($sessionId, $userId, $ipAddress, $reason);
    }
}
