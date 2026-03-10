<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Service;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Factory\AuthTokenFactoryInterface;
use App\User\Application\Service\TwoFactorEventPublisher;
use App\User\Domain\Event\AllSessionsRevokedEvent;
use App\User\Domain\Event\RecoveryCodeUsedEvent;
use App\User\Domain\Event\TwoFactorCompletedEvent;
use App\User\Domain\Event\TwoFactorDisabledEvent;
use App\User\Domain\Event\TwoFactorEnabledEvent;
use App\User\Domain\Event\TwoFactorFailedEvent;
use PHPUnit\Framework\MockObject\MockObject;

final class TwoFactorEventPublisherTest extends UnitTestCase
{
    private EventBusInterface&MockObject $eventBus;
    private AuthTokenFactoryInterface&MockObject $authTokenFactory;
    private TwoFactorEventPublisher $publisher;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->authTokenFactory = $this->createMock(AuthTokenFactoryInterface::class);
        $this->publisher = new TwoFactorEventPublisher(
            $this->eventBus,
            $this->authTokenFactory
        );
    }

    public function testPublishEnabled(): void
    {
        $userId = $this->faker->uuid();
        $email = $this->faker->email();
        $eventId = $this->faker->uuid();

        $this->authTokenFactory->expects($this->once())
            ->method('nextEventId')
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

        $this->authTokenFactory->expects($this->once())
            ->method('nextEventId')
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
        $userId = $this->faker->uuid();
        $sessionId = $this->faker->uuid();
        $ipAddress = $this->faker->ipv4();
        $userAgent = $this->faker->userAgent();
        $verificationMethod = $this->faker->word();
        $eventId = $this->faker->uuid();

        $validator = $this->buildCompletedValidator(
            $userId,
            $sessionId,
            $ipAddress,
            $userAgent,
            $verificationMethod,
            $eventId
        );
        $this->arrangeEventId($eventId);
        $this->expectEventPublished($validator);
        $this->callCompleted($userId, $sessionId, $ipAddress, $userAgent, $verificationMethod);
    }

    public function testPublishCompletedWithNullVerificationMethod(): void
    {
        $userId = $this->faker->uuid();
        $sessionId = $this->faker->uuid();
        $ipAddress = $this->faker->ipv4();
        $userAgent = $this->faker->userAgent();
        $eventId = $this->faker->uuid();

        $this->authTokenFactory->expects($this->once())
            ->method('nextEventId')
            ->willReturn($eventId);

        $this->eventBus->expects($this->once())
            ->method('publish')
            ->with($this->callback(
                static function (TwoFactorCompletedEvent $event): bool {
                    return $event->method === '';
                }
            ));

        $this->publisher->publishCompleted($userId, $sessionId, $ipAddress, $userAgent, null);
    }

    public function testPublishFailed(): void
    {
        $pendingSessionId = $this->faker->uuid();
        $ipAddress = $this->faker->ipv4();
        $reason = $this->faker->sentence();
        $eventId = $this->faker->uuid();

        $this->authTokenFactory->expects($this->once())
            ->method('nextEventId')
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

        $this->authTokenFactory->expects($this->once())
            ->method('nextEventId')
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

    public function testPublishAllSessionsRevoked(): void
    {
        $userId = $this->faker->uuid();
        $reason = $this->faker->sentence();
        $revokedCount = $this->faker->numberBetween(1, 20);
        $eventId = $this->faker->uuid();

        $this->authTokenFactory->expects($this->once())
            ->method('nextEventId')
            ->willReturn($eventId);

        $this->eventBus->expects($this->once())
            ->method('publish')
            ->with($this->callback(
                $this->buildAllSessionsRevokedValidator($userId, $reason, $revokedCount, $eventId)
            ));

        $this->publisher->publishAllSessionsRevoked($userId, $reason, $revokedCount);
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
            $sessionId,
            $ipAddress,
            $userAgent,
            $verificationMethod
        );
    }

    private function arrangeEventId(string $eventId): void
    {
        $this->authTokenFactory->expects($this->once())
            ->method('nextEventId')
            ->willReturn($eventId);
    }

    private function expectEventPublished(callable $validator): void
    {
        $this->eventBus->expects($this->once())
            ->method('publish')
            ->with($this->callback($validator));
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

    private function buildAllSessionsRevokedValidator(
        string $userId,
        string $reason,
        int $revokedCount,
        string $eventId
    ): callable {
        return static function (AllSessionsRevokedEvent $event) use (
            $userId,
            $reason,
            $revokedCount,
            $eventId
        ): bool {
            return $event->userId === $userId
                && $event->reason === $reason
                && $event->revokedCount === $revokedCount
                && $event->eventId() === $eventId;
        };
    }
}
