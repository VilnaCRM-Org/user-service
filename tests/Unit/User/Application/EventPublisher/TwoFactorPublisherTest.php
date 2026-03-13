<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\EventPublisher;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Factory\EventIdFactoryInterface;
use App\User\Domain\Event\RecoveryCodeUsedEvent;
use App\User\Domain\Event\TwoFactorCompletedEvent;
use App\User\Domain\Event\TwoFactorDisabledEvent;
use App\User\Domain\Event\TwoFactorEnabledEvent;
use App\User\Domain\Event\TwoFactorFailedEvent;
use App\User\Domain\Event\UserSignedInEvent;
use App\User\Infrastructure\Publisher\TwoFactorPublisher;
use PHPUnit\Framework\MockObject\MockObject;

final class TwoFactorPublisherTest extends UnitTestCase
{
    private EventBusInterface&MockObject $eventBus;
    private EventIdFactoryInterface&MockObject $eventIdFactory;
    private TwoFactorPublisher $publisher;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->eventIdFactory = $this->createMock(EventIdFactoryInterface::class);
        $this->publisher = new TwoFactorPublisher(
            $this->eventBus,
            $this->eventIdFactory
        );
    }

    public function testPublishEnabled(): void
    {
        $userId = $this->faker->uuid();
        $email = $this->faker->email();
        $eventId = $this->faker->uuid();

        $this->eventIdFactory->expects($this->once())
            ->method('generate')
            ->willReturn($eventId);

        $this->eventBus->expects($this->once())
            ->method('publish')
            ->with($this->callback(
                static function (TwoFactorEnabledEvent $event) use (
                    $userId,
                    $email,
                    $eventId
                ): bool {
                    return $event->userId === $userId
                        && $event->email === $email
                        && $event->eventId() === $eventId;
                }
            ));

        $this->publisher->publishEnabled($userId, $email);
    }

    public function testPublishDisabled(): void
    {
        $userId = $this->faker->uuid();
        $email = $this->faker->email();
        $eventId = $this->faker->uuid();

        $this->eventIdFactory->expects($this->once())
            ->method('generate')
            ->willReturn($eventId);

        $this->eventBus->expects($this->once())
            ->method('publish')
            ->with($this->callback(
                static function (TwoFactorDisabledEvent $event) use (
                    $userId,
                    $email,
                    $eventId
                ): bool {
                    return $event->userId === $userId
                        && $event->email === $email
                        && $event->eventId() === $eventId;
                }
            ));

        $this->publisher->publishDisabled($userId, $email);
    }

    public function testPublishCompleted(): void
    {
        $uid = $this->faker->uuid();
        $sid = $this->faker->uuid();
        $ip = $this->faker->ipv4();
        $ua = $this->faker->userAgent();
        $method = $this->faker->word();
        $eid = $this->faker->uuid();

        $this->eventIdFactory->method('generate')->willReturn($eid);
        $events = [];
        $this->capturePublishedEvents(2, $events);
        $this->callCompleted($uid, $sid, $ip, $ua, $method);

        $this->assertCompletedEvents($events, $uid, $sid, $ip, $ua, $method, $eid);
    }

    public function testPublishCompletedWithNullVerificationMethod(): void
    {
        [$userId, $sessionId, $ip, $ua] = $this->arrangeCompletedFixtures();
        $events = [];
        $this->capturePublishedEvents(2, $events);

        $this->publisher->publishCompleted(
            $userId,
            $this->faker->email(),
            $sessionId,
            $ip,
            $ua,
            null
        );

        $this->assertInstanceOf(TwoFactorCompletedEvent::class, $events[0]);
        $this->assertSame('', $events[0]->method);
    }

    public function testPublishFailed(): void
    {
        $pendingSessionId = $this->faker->uuid();
        $ipAddress = $this->faker->ipv4();
        $reason = $this->faker->sentence();
        $eventId = $this->faker->uuid();

        $this->eventIdFactory->expects($this->once())
            ->method('generate')
            ->willReturn($eventId);

        $this->eventBus->expects($this->once())
            ->method('publish')
            ->with($this->callback(
                $this->buildFailedValidator($pendingSessionId, $ipAddress, $reason, $eventId)
            ));

        $this->publisher->publishFailed($pendingSessionId, $ipAddress, $reason);
    }

    public function testPublishRecoveryCodeUsed(): void
    {
        $userId = $this->faker->uuid();
        $remainingCount = $this->faker->numberBetween(0, 10);
        $eventId = $this->faker->uuid();

        $this->eventIdFactory->expects($this->once())
            ->method('generate')
            ->willReturn($eventId);

        $this->eventBus->expects($this->once())
            ->method('publish')
            ->with($this->callback(
                static function (RecoveryCodeUsedEvent $event) use (
                    $userId,
                    $remainingCount,
                    $eventId
                ): bool {
                    return $event->userId === $userId
                        && $event->remainingCount === $remainingCount
                        && $event->eventId() === $eventId;
                }
            ));

        $this->publisher->publishRecoveryCodeUsed($userId, $remainingCount);
    }

    /**
     * @param array<object> $events
     */
    private function capturePublishedEvents(
        int $count,
        array &$events
    ): void {
        $this->eventBus->expects($this->exactly($count))
            ->method('publish')
            ->willReturnCallback(
                static function (object $e) use (&$events): void {
                    $events[] = $e;
                }
            );
    }

    /**
     * @param array<object> $events
     */
    private function assertCompletedEvents(
        array $events,
        string $uid,
        string $sid,
        string $ip,
        string $ua,
        string $method,
        string $eid
    ): void {
        $v = $this->buildCompletedValidator($uid, $sid, $ip, $ua, $method, $eid);
        $this->assertInstanceOf(TwoFactorCompletedEvent::class, $events[0]);
        $this->assertTrue($v($events[0]));
        $this->assertInstanceOf(UserSignedInEvent::class, $events[1]);
        $this->assertTrue($events[1]->twoFactorUsed);
    }

    private function callCompleted(
        string $userId,
        string $sessionId,
        string $ipAddress,
        string $userAgent,
        string $verificationMethod
    ): void {
        $this->publisher->publishCompleted(
            $userId,
            $this->faker->email(),
            $sessionId,
            $ipAddress,
            $userAgent,
            $verificationMethod
        );
    }

    /**
     * @return array{string, string, string, string}
     */
    private function arrangeCompletedFixtures(): array
    {
        $this->eventIdFactory->method('generate')
            ->willReturn($this->faker->uuid());

        return [
            $this->faker->uuid(),
            $this->faker->uuid(),
            $this->faker->ipv4(),
            $this->faker->userAgent(),
        ];
    }

    private function buildCompletedValidator(
        string $userId,
        string $sessionId,
        string $ipAddress,
        string $userAgent,
        string $verificationMethod,
        string $eventId
    ): callable {
        return static function (TwoFactorCompletedEvent $event) use (
            $userId,
            $sessionId,
            $ipAddress,
            $userAgent,
            $verificationMethod,
            $eventId
        ): bool {
            return $event->userId === $userId
                && $event->sessionId === $sessionId
                && $event->ipAddress === $ipAddress
                && $event->userAgent === $userAgent
                && $event->method === $verificationMethod
                && $event->eventId() === $eventId;
        };
    }

    private function buildFailedValidator(
        string $pendingSessionId,
        string $ipAddress,
        string $reason,
        string $eventId
    ): callable {
        return static function (TwoFactorFailedEvent $event) use (
            $pendingSessionId,
            $ipAddress,
            $reason,
            $eventId
        ): bool {
            return $event->pendingSessionId === $pendingSessionId
                && $event->ipAddress === $ipAddress
                && $event->reason === $reason
                && $event->eventId() === $eventId;
        };
    }
}
