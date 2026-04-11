<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Infrastructure\Publisher;

use App\OAuth\Domain\Event\OAuthUserCreatedEvent;
use App\OAuth\Domain\Event\OAuthUserSignedInEvent;
use App\OAuth\Domain\Factory\Event\OAuthEventFactoryInterface;
use App\OAuth\Infrastructure\Publisher\OAuthPublisher;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Factory\EventIdFactoryInterface;
use PHPUnit\Framework\MockObject\MockObject;

final class OAuthPublisherTest extends UnitTestCase
{
    private EventBusInterface&MockObject $eventBus;
    private EventIdFactoryInterface&MockObject $eventIdFactory;
    private OAuthEventFactoryInterface&MockObject $oAuthEventFactory;
    private OAuthPublisher $publisher;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->eventIdFactory = $this->createMock(EventIdFactoryInterface::class);
        $this->oAuthEventFactory = $this->createMock(OAuthEventFactoryInterface::class);

        $this->publisher = new OAuthPublisher(
            $this->eventBus,
            $this->eventIdFactory,
            $this->oAuthEventFactory,
        );
    }

    public function testPublishUserCreatedPublishesEvent(): void
    {
        $userId = $this->faker->uuid();
        $email = $this->faker->safeEmail();
        $provider = $this->faker->word();
        $eventId = $this->faker->uuid();

        $event = new OAuthUserCreatedEvent($userId, $email, $provider, $eventId);

        $this->eventIdFactory->expects($this->once())
            ->method('generate')
            ->willReturn($eventId);
        $this->oAuthEventFactory->expects($this->once())
            ->method('createUserCreated')
            ->with($userId, $email, $provider, $eventId)
            ->willReturn($event);

        $this->eventBus->expects($this->once())
            ->method('publish')
            ->with($event);

        $this->publisher->publishUserCreated($userId, $email, $provider);
    }

    public function testPublishUserSignedInPublishesEvent(): void
    {
        $userId = $this->faker->uuid();
        $email = $this->faker->safeEmail();
        $provider = $this->faker->word();
        $sessionId = $this->faker->uuid();

        $this->expectSignedInPublication(
            $userId,
            $email,
            $provider,
            $sessionId,
        );

        $this->publisher->publishUserSignedIn($userId, $email, $provider, $sessionId);
    }

    public function testPublishUserCreatedDoesNotPublishWhenEventIdGenerationFails(): void
    {
        $userId = $this->faker->uuid();
        $email = $this->faker->safeEmail();
        $provider = $this->faker->word();
        $exception = new \RuntimeException($this->faker->sentence());

        $this->eventIdFactory->expects($this->once())
            ->method('generate')
            ->willThrowException($exception);
        $this->oAuthEventFactory->expects($this->never())
            ->method('createUserCreated');
        $this->eventBus->expects($this->never())
            ->method('publish');

        $this->expectExceptionObject($exception);

        $this->publisher->publishUserCreated($userId, $email, $provider);
    }

    public function testPublishUserSignedInDoesNotPublishWhenEventFactoryFails(): void
    {
        $userId = $this->faker->uuid();
        $email = $this->faker->safeEmail();
        $provider = $this->faker->word();
        $sessionId = $this->faker->uuid();
        $eventId = $this->faker->uuid();
        $exception = new \RuntimeException($this->faker->sentence());

        $this->eventIdFactory->expects($this->once())
            ->method('generate')
            ->willReturn($eventId);
        $this->expectSignedInEventFactoryFailure(
            $userId,
            $email,
            $provider,
            $sessionId,
            $eventId,
            $exception,
        );
        $this->expectNoPublishedEvents();

        $this->expectExceptionObject($exception);

        $this->publisher->publishUserSignedIn($userId, $email, $provider, $sessionId);
    }

    private function createSignedInEvent(
        string $userId,
        string $email,
        string $provider,
        string $sessionId,
        string $eventId,
    ): OAuthUserSignedInEvent {
        return new OAuthUserSignedInEvent(
            $userId,
            $email,
            $provider,
            $sessionId,
            $eventId,
        );
    }

    private function expectSignedInPublication(
        string $userId,
        string $email,
        string $provider,
        string $sessionId,
    ): void {
        $eventId = $this->faker->uuid();
        $event = $this->createSignedInEvent(
            $userId,
            $email,
            $provider,
            $sessionId,
            $eventId,
        );

        $this->eventIdFactory->expects($this->once())
            ->method('generate')
            ->willReturn($eventId);
        $this->expectSignedInEventFactory(
            $userId,
            $email,
            $provider,
            $sessionId,
            $eventId,
            $event,
        );
        $this->expectEventPublished($event);
    }

    private function expectSignedInEventFactory(
        string $userId,
        string $email,
        string $provider,
        string $sessionId,
        string $eventId,
        OAuthUserSignedInEvent $event,
    ): void {
        $this->oAuthEventFactory->expects($this->once())
            ->method('createUserSignedIn')
            ->with($userId, $email, $provider, $sessionId, $eventId)
            ->willReturn($event);
    }

    private function expectSignedInEventFactoryFailure(
        string $userId,
        string $email,
        string $provider,
        string $sessionId,
        string $eventId,
        \RuntimeException $exception,
    ): void {
        $this->oAuthEventFactory->expects($this->once())
            ->method('createUserSignedIn')
            ->with($userId, $email, $provider, $sessionId, $eventId)
            ->willThrowException($exception);
    }

    private function expectEventPublished(object $event): void
    {
        $this->eventBus->expects($this->once())
            ->method('publish')
            ->with($event);
    }

    private function expectNoPublishedEvents(): void
    {
        $this->eventBus->expects($this->never())
            ->method('publish');
    }
}
