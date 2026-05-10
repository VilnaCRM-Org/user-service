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
use App\User\Domain\Factory\Event\SignInEventFactoryInterface;
use App\User\Domain\Factory\Event\TwoFactorEventFactoryInterface;
use App\User\Infrastructure\Publisher\TwoFactorPublisher;
use PHPUnit\Framework\MockObject\MockObject;

final class TwoFactorPublisherTest extends UnitTestCase
{
    private EventBusInterface&MockObject $eventBus;
    private EventIdFactoryInterface&MockObject $eventIdFactory;
    private TwoFactorEventFactoryInterface&MockObject $twoFactorEventFactory;
    private SignInEventFactoryInterface&MockObject $signInEventFactory;
    private TwoFactorPublisher $publisher;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->eventIdFactory = $this->createMock(EventIdFactoryInterface::class);
        $this->twoFactorEventFactory = $this->createMock(TwoFactorEventFactoryInterface::class);
        $this->signInEventFactory = $this->createMock(SignInEventFactoryInterface::class);
        $this->publisher = new TwoFactorPublisher(
            $this->eventBus,
            $this->eventIdFactory,
            $this->twoFactorEventFactory,
            $this->signInEventFactory
        );
    }

    public function testPublishEnabled(): void
    {
        $userId = $this->faker->uuid();
        $email = $this->faker->email();
        $eventId = $this->faker->uuid();
        $event = $this->createMock(TwoFactorEnabledEvent::class);

        $this->expectGeneratedEventIds($eventId);
        $this->twoFactorEventFactory->expects($this->once())
            ->method('createEnabled')
            ->with($userId, $email, $eventId)
            ->willReturn($event);
        $this->expectPublishedEvent($event);

        $this->publisher->publishEnabled($userId, $email);
    }

    public function testPublishDisabled(): void
    {
        $userId = $this->faker->uuid();
        $email = $this->faker->email();
        $eventId = $this->faker->uuid();
        $event = $this->createMock(TwoFactorDisabledEvent::class);

        $this->expectGeneratedEventIds($eventId);
        $this->twoFactorEventFactory->expects($this->once())
            ->method('createDisabled')
            ->with($userId, $email, $eventId)
            ->willReturn($event);
        $this->expectPublishedEvent($event);

        $this->publisher->publishDisabled($userId, $email);
    }

    public function testPublishCompleted(): void
    {
        [$completedEvent, $signedInEvent, $publishedEvents] = $this->assertPublishCompleted(
            $this->faker->word()
        );
        $this->assertSame([$completedEvent, $signedInEvent], $publishedEvents);
    }

    public function testPublishCompletedWithNullVerificationMethod(): void
    {
        $this->assertPublishCompletedWithoutCapture(null);
    }

    public function testPublishFailed(): void
    {
        $pendingSessionId = $this->faker->uuid();
        $ipAddress = $this->faker->ipv4();
        $reason = $this->faker->sentence();
        $eventId = $this->faker->uuid();
        $event = $this->createMock(TwoFactorFailedEvent::class);

        $this->expectGeneratedEventIds($eventId);
        $this->twoFactorEventFactory->expects($this->once())
            ->method('createFailed')
            ->with($pendingSessionId, $ipAddress, $reason, $eventId)
            ->willReturn($event);
        $this->expectPublishedEvent($event);

        $this->publisher->publishFailed($pendingSessionId, $ipAddress, $reason);
    }

    public function testPublishRecoveryCodeUsed(): void
    {
        $userId = $this->faker->uuid();
        $remainingCount = $this->faker->numberBetween(0, 10);
        $eventId = $this->faker->uuid();
        $event = $this->createMock(RecoveryCodeUsedEvent::class);

        $this->expectGeneratedEventIds($eventId);
        $this->twoFactorEventFactory->expects($this->once())
            ->method('createRecoveryCodeUsed')
            ->with($userId, $remainingCount, $eventId)
            ->willReturn($event);
        $this->expectPublishedEvent($event);

        $this->publisher->publishRecoveryCodeUsed($userId, $remainingCount);
    }

    private function expectGeneratedEventIds(string ...$eventIds): void
    {
        $this->eventIdFactory->expects($this->exactly(count($eventIds)))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(...$eventIds);
    }

    private function expectPublishedEvent(object $event): void
    {
        $this->eventBus->expects($this->once())
            ->method('publish')
            ->with($event);
    }

    /**
     * @return array{0: TwoFactorCompletedEvent, 1: UserSignedInEvent, 2: list<object>}
     */
    private function assertPublishCompleted(?string $verificationMethod): array
    {
        $scenario = $this->createCompletedScenario($verificationMethod);
        $publishedEvents = [];
        $this->expectGeneratedEventIds($scenario['completedEventId'], $scenario['signedInEventId']);
        $this->expectCompletedEventFactoryCall($scenario);
        $this->expectCompletedSignInFactoryCall($scenario);
        $this->captureCompletedPublication($publishedEvents);
        $this->publishCompletedScenario($scenario);

        return [$scenario['completedEvent'], $scenario['signedInEvent'], $publishedEvents];
    }

    private function assertPublishCompletedWithoutCapture(?string $verificationMethod): void
    {
        $scenario = $this->createCompletedScenario($verificationMethod);
        $this->expectGeneratedEventIds($scenario['completedEventId'], $scenario['signedInEventId']);
        $this->expectCompletedEventFactoryCall($scenario);
        $this->expectCompletedSignInFactoryCall($scenario);
        $this->expectCompletedPublication();
        $this->publishCompletedScenario($scenario);
    }

    /**
     * @return array{
     *     completedEvent: TwoFactorCompletedEvent,
     *     completedEventId: string,
     *     email: string,
     *     ipAddress: string,
     *     sessionId: string,
     *     signedInEvent: UserSignedInEvent,
     *     signedInEventId: string,
     *     userAgent: string,
     *     userId: string,
     *     verificationMethod: string
     * }
     */
    private function createCompletedScenario(?string $verificationMethod): array
    {
        return [
            'completedEvent' => $this->createMock(TwoFactorCompletedEvent::class),
            'completedEventId' => $this->faker->uuid(),
            'email' => $this->faker->email(),
            'ipAddress' => $this->faker->ipv4(),
            'sessionId' => $this->faker->uuid(),
            'signedInEvent' => $this->createMock(UserSignedInEvent::class),
            'signedInEventId' => $this->faker->uuid(),
            'userAgent' => $this->faker->userAgent(),
            'userId' => $this->faker->uuid(),
            'verificationMethod' => $verificationMethod ?? '',
        ];
    }

    /**
     * @param array{
     *     completedEvent: TwoFactorCompletedEvent,
     *     completedEventId: string,
     *     ipAddress: string,
     *     sessionId: string,
     *     userAgent: string,
     *     userId: string,
     *     verificationMethod: string
     * } $scenario
     */
    private function expectCompletedEventFactoryCall(array $scenario): void
    {
        $this->twoFactorEventFactory->expects($this->once())
            ->method('createCompleted')
            ->with(
                $scenario['userId'],
                $scenario['sessionId'],
                $scenario['ipAddress'],
                $scenario['userAgent'],
                $scenario['verificationMethod'],
                $scenario['completedEventId']
            )
            ->willReturn($scenario['completedEvent']);
    }

    /**
     * @param array{
     *     email: string,
     *     ipAddress: string,
     *     sessionId: string,
     *     signedInEvent: UserSignedInEvent,
     *     signedInEventId: string,
     *     userAgent: string,
     *     userId: string
     * } $scenario
     */
    private function expectCompletedSignInFactoryCall(array $scenario): void
    {
        $this->signInEventFactory->expects($this->once())
            ->method('createSignedIn')
            ->with(
                $scenario['userId'],
                $scenario['email'],
                $scenario['sessionId'],
                $scenario['ipAddress'],
                $scenario['userAgent'],
                true,
                $scenario['signedInEventId']
            )
            ->willReturn($scenario['signedInEvent']);
    }

    /**
     * @param list<object> $publishedEvents
     */
    private function captureCompletedPublication(array &$publishedEvents): void
    {
        $this->eventBus->expects($this->exactly(2))
            ->method('publish')
            ->willReturnCallback(
                static function (object $event) use (&$publishedEvents): void {
                    $publishedEvents[] = $event;
                }
            );
    }

    private function expectCompletedPublication(): void
    {
        $this->eventBus->expects($this->exactly(2))
            ->method('publish')
            ->withAnyParameters();
    }

    /**
     * @param array{
     *     email: string,
     *     ipAddress: string,
     *     sessionId: string,
     *     userAgent: string,
     *     userId: string,
     *     verificationMethod: string
     * } $scenario
     */
    private function publishCompletedScenario(array $scenario): void
    {
        $this->publisher->publishCompleted(
            $scenario['userId'],
            $scenario['email'],
            $scenario['sessionId'],
            $scenario['ipAddress'],
            $scenario['userAgent'],
            $scenario['verificationMethod']
        );
    }
}
