<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\EventPublisher;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Factory\EventIdFactoryInterface;
use App\User\Domain\Event\AccountLockedOutEvent;
use App\User\Domain\Event\SignInFailedEvent;
use App\User\Domain\Event\UserSignedInEvent;
use App\User\Domain\Factory\Event\SignInEventFactoryInterface;
use App\User\Infrastructure\Publisher\SignInPublisher;
use PHPUnit\Framework\MockObject\MockObject;

final class SignInPublisherTest extends UnitTestCase
{
    private EventBusInterface&MockObject $eventBus;
    private EventIdFactoryInterface&MockObject $eventIdFactory;
    private SignInEventFactoryInterface&MockObject $signInEventFactory;
    private SignInPublisher $publisher;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->eventIdFactory = $this->createMock(EventIdFactoryInterface::class);
        $this->signInEventFactory = $this->createMock(SignInEventFactoryInterface::class);
        $this->publisher = new SignInPublisher(
            $this->eventBus,
            $this->eventIdFactory,
            $this->signInEventFactory
        );
    }

    public function testPublishSignedIn(): void
    {
        $this->assertSignedInPublication($this->faker->boolean());
    }

    public function testPublishSignedInWithTwoFactorUsed(): void
    {
        $this->assertSignedInPublication(true);
    }

    public function testPublishSignedInWithoutTwoFactor(): void
    {
        $this->assertSignedInPublication(false);
    }

    public function testPublishFailed(): void
    {
        $email = $this->faker->email();
        $ipAddress = $this->faker->ipv4();
        $userAgent = $this->faker->userAgent();
        $reason = $this->faker->sentence();
        $eventId = $this->faker->uuid();
        $event = $this->createMock(SignInFailedEvent::class);

        $this->expectGeneratedEventId($eventId);
        $this->signInEventFactory->expects($this->once())
            ->method('createFailed')
            ->with($email, $ipAddress, $userAgent, $reason, $eventId)
            ->willReturn($event);
        $this->expectPublishedEvent($event);

        $this->publisher->publishFailed($email, $ipAddress, $userAgent, $reason);
    }

    public function testPublishLockedOut(): void
    {
        $email = $this->faker->email();
        $failedAttempts = $this->faker->numberBetween(3, 10);
        $lockoutDurationSeconds = $this->faker->numberBetween(60, 3600);
        $eventId = $this->faker->uuid();
        $event = $this->createMock(AccountLockedOutEvent::class);

        $this->expectGeneratedEventId($eventId);
        $this->signInEventFactory->expects($this->once())
            ->method('createLockedOut')
            ->with($email, $failedAttempts, $lockoutDurationSeconds, $eventId)
            ->willReturn($event);
        $this->expectPublishedEvent($event);

        $this->publisher->publishLockedOut($email, $failedAttempts, $lockoutDurationSeconds);
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

    private function assertSignedInPublication(bool $twoFactorUsed): void
    {
        $payload = $this->signedInPayload($twoFactorUsed);
        $event = $this->createMock(UserSignedInEvent::class);
        $this->expectGeneratedEventId($payload['eventId']);
        $this->expectCreateSignedIn($payload, $event);
        $this->expectPublishedEvent($event);
        $this->publisher->publishSignedIn(
            $payload['userId'],
            $payload['email'],
            $payload['sessionId'],
            $payload['ipAddress'],
            $payload['userAgent'],
            $payload['twoFactorUsed']
        );
    }

    /**
     * @return array{
     *     email: string,
     *     eventId: string,
     *     ipAddress: string,
     *     sessionId: string,
     *     twoFactorUsed: bool,
     *     userAgent: string,
     *     userId: string
     * }
     */
    private function signedInPayload(bool $twoFactorUsed): array
    {
        return [
            'email' => $this->faker->email(),
            'eventId' => $this->faker->uuid(),
            'ipAddress' => $this->faker->ipv4(),
            'sessionId' => $this->faker->uuid(),
            'twoFactorUsed' => $twoFactorUsed,
            'userAgent' => $this->faker->userAgent(),
            'userId' => $this->faker->uuid(),
        ];
    }

    /**
     * @param array{
     *     email: string,
     *     eventId: string,
     *     ipAddress: string,
     *     sessionId: string,
     *     twoFactorUsed: bool,
     *     userAgent: string,
     *     userId: string
     * } $payload
     */
    private function expectCreateSignedIn(array $payload, UserSignedInEvent $event): void
    {
        $this->signInEventFactory->expects($this->once())
            ->method('createSignedIn')
            ->with(
                $payload['userId'],
                $payload['email'],
                $payload['sessionId'],
                $payload['ipAddress'],
                $payload['userAgent'],
                $payload['twoFactorUsed'],
                $payload['eventId']
            )
            ->willReturn($event);
    }
}
