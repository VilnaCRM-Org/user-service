<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\EventPublisher;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Factory\Generator\EventIdGeneratorInterface;
use App\User\Application\Processor\EventPublisher\SessionEvents;
use App\User\Domain\Event\AllSessionsRevokedEvent;
use App\User\Domain\Event\SessionRevokedEvent;
use PHPUnit\Framework\MockObject\MockObject;

final class SessionEventsTest extends UnitTestCase
{
    private EventBusInterface&MockObject $eventBus;
    private EventIdGeneratorInterface&MockObject $eventIdGenerator;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->eventIdGenerator = $this->createMock(EventIdGeneratorInterface::class);
    }

    public function testPublishSessionRevokedDispatchesEvent(): void
    {
        $this->eventIdGenerator->method('generate')->willReturn($this->faker->uuid());

        $this->eventBus->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(SessionRevokedEvent::class));

        $service = new SessionEvents($this->eventBus, $this->eventIdGenerator);
        $service->publishSessionRevoked(
            $this->faker->uuid(),
            $this->faker->uuid(),
            'user_requested'
        );
    }

    public function testPublishAllSessionsRevokedDispatchesEvent(): void
    {
        $this->eventIdGenerator->method('generate')->willReturn($this->faker->uuid());

        $this->eventBus->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(AllSessionsRevokedEvent::class));

        $service = new SessionEvents($this->eventBus, $this->eventIdGenerator);
        $service->publishAllSessionsRevoked(
            $this->faker->uuid(),
            'password_changed',
            $this->faker->numberBetween(1, 10)
        );
    }
}
