<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Service;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Factory\AuthTokenFactoryInterface;
use App\User\Application\Service\SignInEventPublisher;
use App\User\Domain\Event\AccountLockedOutEvent;
use App\User\Domain\Event\SignInFailedEvent;
use App\User\Domain\Event\UserSignedInEvent;
use PHPUnit\Framework\MockObject\MockObject;

final class SignInEventPublisherTest extends UnitTestCase
{
    private EventBusInterface&MockObject $eventBus;
    private AuthTokenFactoryInterface&MockObject $authTokenFactory;
    private SignInEventPublisher $publisher;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->authTokenFactory = $this->createMock(AuthTokenFactoryInterface::class);
        $this->publisher = new SignInEventPublisher(
            $this->eventBus,
            $this->authTokenFactory
        );
    }

    public function testPublishSignedIn(): void
    {
        $userId = $this->faker->uuid();
        $email = $this->faker->email();
        $sessionId = $this->faker->uuid();
        $ipAddress = $this->faker->ipv4();
        $userAgent = $this->faker->userAgent();
        $twoFactorUsed = $this->faker->boolean();
        $eventId = $this->faker->uuid();

        $this->authTokenFactory->expects($this->once())
            ->method('nextEventId')
            ->willReturn($eventId);

        $this->eventBus->expects($this->once())
            ->method('publish')
            ->with($this->callback(
                static function (UserSignedInEvent $event) use (
                    $userId,
                    $email,
                    $sessionId,
                    $ipAddress,
                    $userAgent,
                    $twoFactorUsed,
                    $eventId
                ): bool {
                    return $event->userId === $userId
                        && $event->email === $email
                        && $event->sessionId === $sessionId
                        && $event->ipAddress === $ipAddress
                        && $event->userAgent === $userAgent
                        && $event->twoFactorUsed === $twoFactorUsed
                        && $event->eventId() === $eventId;
                }
            ));

        $this->publisher->publishSignedIn(
            $userId,
            $email,
            $sessionId,
            $ipAddress,
            $userAgent,
            $twoFactorUsed
        );
    }

    public function testPublishSignedInWithTwoFactorUsed(): void
    {
        $userId = $this->faker->uuid();
        $email = $this->faker->email();
        $sessionId = $this->faker->uuid();
        $ipAddress = $this->faker->ipv4();
        $userAgent = $this->faker->userAgent();
        $eventId = $this->faker->uuid();

        $this->authTokenFactory->method('nextEventId')->willReturn($eventId);

        $this->eventBus->expects($this->once())
            ->method('publish')
            ->with($this->callback(
                static function (UserSignedInEvent $event): bool {
                    return $event->twoFactorUsed === true;
                }
            ));

        $this->publisher->publishSignedIn(
            $userId,
            $email,
            $sessionId,
            $ipAddress,
            $userAgent,
            true
        );
    }

    public function testPublishSignedInWithoutTwoFactor(): void
    {
        $userId = $this->faker->uuid();
        $email = $this->faker->email();
        $sessionId = $this->faker->uuid();
        $ipAddress = $this->faker->ipv4();
        $userAgent = $this->faker->userAgent();
        $eventId = $this->faker->uuid();

        $this->authTokenFactory->method('nextEventId')->willReturn($eventId);

        $this->eventBus->expects($this->once())
            ->method('publish')
            ->with($this->callback(
                static function (UserSignedInEvent $event): bool {
                    return $event->twoFactorUsed === false;
                }
            ));

        $this->publisher->publishSignedIn(
            $userId,
            $email,
            $sessionId,
            $ipAddress,
            $userAgent,
            false
        );
    }

    public function testPublishFailed(): void
    {
        $email = $this->faker->email();
        $ipAddress = $this->faker->ipv4();
        $userAgent = $this->faker->userAgent();
        $reason = $this->faker->sentence();
        $eventId = $this->faker->uuid();

        $this->authTokenFactory->expects($this->once())
            ->method('nextEventId')
            ->willReturn($eventId);

        $this->eventBus->expects($this->once())
            ->method('publish')
            ->with($this->callback(
                static function (SignInFailedEvent $event) use (
                    $email,
                    $ipAddress,
                    $userAgent,
                    $reason,
                    $eventId
                ): bool {
                    return $event->email === $email
                        && $event->ipAddress === $ipAddress
                        && $event->userAgent === $userAgent
                        && $event->reason === $reason
                        && $event->eventId() === $eventId;
                }
            ));

        $this->publisher->publishFailed($email, $ipAddress, $userAgent, $reason);
    }

    public function testPublishLockedOut(): void
    {
        $email = $this->faker->email();
        $failedAttempts = $this->faker->numberBetween(3, 10);
        $lockoutDurationSeconds = $this->faker->numberBetween(60, 3600);
        $eventId = $this->faker->uuid();

        $this->authTokenFactory->expects($this->once())
            ->method('nextEventId')
            ->willReturn($eventId);

        $this->eventBus->expects($this->once())
            ->method('publish')
            ->with($this->callback(
                static function (AccountLockedOutEvent $event) use (
                    $email,
                    $failedAttempts,
                    $lockoutDurationSeconds,
                    $eventId
                ): bool {
                    return $event->email === $email
                        && $event->failedAttempts === $failedAttempts
                        && $event->lockoutDurationSeconds === $lockoutDurationSeconds
                        && $event->eventId() === $eventId;
                }
            ));

        $this->publisher->publishLockedOut($email, $failedAttempts, $lockoutDurationSeconds);
    }
}
