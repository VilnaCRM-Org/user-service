<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\EventPublisher;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Factory\EventIdFactoryInterface;
use App\User\Domain\Event\AllSessionsRevokedEvent;
use App\User\Domain\Event\SessionRevokedEvent;
use App\User\Infrastructure\Publisher\SessionPublisher;
use PHPUnit\Framework\MockObject\MockObject;

final class SessionPublisherTest extends UnitTestCase
{
    private EventBusInterface&MockObject $eventBus;
    private EventIdFactoryInterface&MockObject $eventIdFactory;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->eventIdFactory = $this->createMock(EventIdFactoryInterface::class);
    }

    public function testPublishSessionRevokedDispatchesEvent(): void
    {
        $this->eventIdFactory->method('generate')->willReturn($this->faker->uuid());

        $this->eventBus->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(SessionRevokedEvent::class));

        $service = new SessionPublisher($this->eventBus, $this->eventIdFactory);
        $service->publishSessionRevoked(
            $this->faker->uuid(),
            $this->faker->uuid(),
            'user_requested'
        );
    }

    public function testPublishAllSessionsRevokedDispatchesEvent(): void
    {
        $this->eventIdFactory->method('generate')->willReturn($this->faker->uuid());

        $this->eventBus->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(AllSessionsRevokedEvent::class));

        $service = new SessionPublisher($this->eventBus, $this->eventIdFactory);
        $service->publishAllSessionsRevoked(
            $this->faker->uuid(),
            'password_changed',
            $this->faker->numberBetween(1, 10)
        );
    }
}
