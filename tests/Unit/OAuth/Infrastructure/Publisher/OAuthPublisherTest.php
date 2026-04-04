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

        $this->eventIdFactory->method('generate')->willReturn($eventId);
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
        $this->arrangeSignedInPublishExpectation($userId, $email, $provider, $sessionId);
        $this->publisher->publishUserSignedIn($userId, $email, $provider, $sessionId);
    }

    private function arrangeSignedInPublishExpectation(
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
        $this->expectSignedInEventToBePublished(
            $userId,
            $email,
            $provider,
            $sessionId,
            $eventId,
            $event,
        );
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

    private function expectSignedInEventToBePublished(
        string $userId,
        string $email,
        string $provider,
        string $sessionId,
        string $eventId,
        OAuthUserSignedInEvent $event,
    ): void {
        $this->eventIdFactory->method('generate')->willReturn($eventId);

        $this->oAuthEventFactory->expects($this->once())
            ->method('createUserSignedIn')
            ->with($userId, $email, $provider, $sessionId, $eventId)
            ->willReturn($event);

        $this->eventBus->expects($this->once())
            ->method('publish')
            ->with($event);
    }
}
