<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\EventPublisher;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Factory\Generator\EventIdGeneratorInterface;
use App\User\Application\Processor\EventPublisher\RefreshTokenEvents;
use App\User\Domain\Event\RefreshTokenRotatedEvent;
use App\User\Domain\Event\RefreshTokenTheftDetectedEvent;
use PHPUnit\Framework\MockObject\MockObject;

final class RefreshTokenEventsTest extends UnitTestCase
{
    private EventBusInterface&MockObject $eventBus;
    private EventIdGeneratorInterface&MockObject $eventIdGenerator;
    private RefreshTokenEvents $publisher;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->eventIdGenerator = $this->createMock(EventIdGeneratorInterface::class);
        $this->publisher = new RefreshTokenEvents(
            $this->eventBus,
            $this->eventIdGenerator
        );
    }

    public function testPublishRotated(): void
    {
        $sessionId = $this->faker->uuid();
        $userId = $this->faker->uuid();
        $eventId = $this->faker->uuid();

        $this->eventIdGenerator->expects($this->once())
            ->method('generate')
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
        $sid = $this->faker->uuid();
        $uid = $this->faker->uuid();
        $ip = $this->faker->ipv4();
        $reason = $this->faker->sentence();
        $eid = $this->faker->uuid();

        $this->eventIdGenerator->expects($this->once())
            ->method('generate')->willReturn($eid);

        $this->eventBus->expects($this->once())
            ->method('publish')
            ->with($this->callback(
                $this->theftValidator($sid, $uid, $ip, $reason, $eid)
            ));

        $this->publisher->publishTheftDetected($sid, $uid, $ip, $reason);
    }

    private function theftValidator(
        string $sid,
        string $uid,
        string $ip,
        string $reason,
        string $eid
    ): callable {
        return static function (RefreshTokenTheftDetectedEvent $e) use (
            $sid,
            $uid,
            $ip,
            $reason,
            $eid
        ): bool {
            return $e->sessionId === $sid
                && $e->userId === $uid
                && $e->ipAddress === $ip
                && $e->reason === $reason
                && $e->eventId() === $eid;
        };
    }
}
