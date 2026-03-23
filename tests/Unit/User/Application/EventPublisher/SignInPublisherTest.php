<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\EventPublisher;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Factory\EventIdFactoryInterface;
use App\User\Domain\Event\AccountLockedOutEvent;
use App\User\Domain\Event\SignInFailedEvent;
use App\User\Domain\Event\UserSignedInEvent;
use App\User\Infrastructure\Publisher\SignInPublisher;
use PHPUnit\Framework\MockObject\MockObject;

final class SignInPublisherTest extends UnitTestCase
{
    private EventBusInterface&MockObject $eventBus;
    private EventIdFactoryInterface&MockObject $eventIdFactory;
    private SignInPublisher $publisher;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->eventIdFactory = $this->createMock(EventIdFactoryInterface::class);
        $this->publisher = new SignInPublisher(
            $this->eventBus,
            $this->eventIdFactory
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

        $validator = $this->buildSignedInValidator(
            $userId,
            $email,
            $sessionId,
            $ipAddress,
            $userAgent,
            $twoFactorUsed,
            $eventId
        );
        $this->arrangeEventId($eventId);
        $this->expectEventPublished($validator);
        $this->callSignedIn($userId, $email, $sessionId, $ipAddress, $userAgent, $twoFactorUsed);
    }

    public function testPublishSignedInWithTwoFactorUsed(): void
    {
        $userId = $this->faker->uuid();
        $email = $this->faker->email();
        $sessionId = $this->faker->uuid();
        $ipAddress = $this->faker->ipv4();
        $userAgent = $this->faker->userAgent();
        $eventId = $this->faker->uuid();

        $this->eventIdFactory->method('generate')->willReturn($eventId);

        $this->eventBus->expects($this->once())
            ->method('publish')
            ->with($this->callback(
                static function (UserSignedInEvent $event): bool {
                    return $event->twoFactorUsed === true;
                }
            ));

        $this->callSignedIn($userId, $email, $sessionId, $ipAddress, $userAgent, true);
    }

    public function testPublishSignedInWithoutTwoFactor(): void
    {
        $userId = $this->faker->uuid();
        $email = $this->faker->email();
        $sessionId = $this->faker->uuid();
        $ipAddress = $this->faker->ipv4();
        $userAgent = $this->faker->userAgent();
        $eventId = $this->faker->uuid();

        $this->eventIdFactory->method('generate')->willReturn($eventId);

        $this->eventBus->expects($this->once())
            ->method('publish')
            ->with($this->callback(
                static function (UserSignedInEvent $event): bool {
                    return $event->twoFactorUsed === false;
                }
            ));

        $this->callSignedIn($userId, $email, $sessionId, $ipAddress, $userAgent, false);
    }

    public function testPublishFailed(): void
    {
        $email = $this->faker->email();
        $ipAddress = $this->faker->ipv4();
        $userAgent = $this->faker->userAgent();
        $reason = $this->faker->sentence();
        $eventId = $this->faker->uuid();

        $this->eventIdFactory->expects($this->once())
            ->method('generate')->willReturn($eventId);

        $this->eventBus->expects($this->once())
            ->method('publish')
            ->with($this->callback(
                $this->buildSignInFailedValidator($email, $ipAddress, $userAgent, $reason, $eventId)
            ));

        $this->publisher->publishFailed($email, $ipAddress, $userAgent, $reason);
    }

    public function testPublishLockedOut(): void
    {
        $email = $this->faker->email();
        $failedAttempts = $this->faker->numberBetween(3, 10);
        $lockoutDurationSeconds = $this->faker->numberBetween(60, 3600);
        $eventId = $this->faker->uuid();

        $this->eventIdFactory->expects($this->once())
            ->method('generate')->willReturn($eventId);

        $this->eventBus->expects($this->once())
            ->method('publish')
            ->with($this->callback(
                $this->buildLockedOutValidator(
                    $email,
                    $failedAttempts,
                    $lockoutDurationSeconds,
                    $eventId
                )
            ));

        $this->publisher->publishLockedOut($email, $failedAttempts, $lockoutDurationSeconds);
    }

    private function callSignedIn(
        string $userId,
        string $email,
        string $sessionId,
        string $ipAddress,
        string $userAgent,
        bool $twoFactorUsed
    ): void {
        $this->publisher->publishSignedIn(
            $userId,
            $email,
            $sessionId,
            $ipAddress,
            $userAgent,
            $twoFactorUsed
        );
    }

    private function arrangeEventId(string $eventId): void
    {
        $this->eventIdFactory->expects($this->once())
            ->method('generate')->willReturn($eventId);
    }

    private function expectEventPublished(callable $validator): void
    {
        $this->eventBus->expects($this->once())
            ->method('publish')
            ->with($this->callback($validator));
    }

    private function buildSignedInValidator(
        string $userId,
        string $email,
        string $sessionId,
        string $ipAddress,
        string $userAgent,
        bool $twoFactorUsed,
        string $eventId
    ): callable {
        return static function (UserSignedInEvent $event) use (
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
        };
    }

    private function buildSignInFailedValidator(
        string $email,
        string $ipAddress,
        string $userAgent,
        string $reason,
        string $eventId
    ): callable {
        return static function (SignInFailedEvent $event) use (
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
        };
    }

    private function buildLockedOutValidator(
        string $email,
        int $failedAttempts,
        int $lockoutDurationSeconds,
        string $eventId
    ): callable {
        return static function (AccountLockedOutEvent $event) use (
            $email,
            $failedAttempts,
            $lockoutDurationSeconds,
            $eventId
        ): bool {
            return $event->email === $email
                && $event->failedAttempts === $failedAttempts
                && $event->lockoutDurationSeconds === $lockoutDurationSeconds
                && $event->eventId() === $eventId;
        };
    }
}
