<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Publisher;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Event\PasswordResetConfirmedEvent;
use App\User\Domain\Event\UserUpdatedEvent;
use App\User\Domain\Factory\Event\PasswordResetConfirmedEventFactoryInterface;
use App\User\Domain\Factory\Event\UserUpdatedEventFactoryInterface;
use App\User\Infrastructure\Publisher\PasswordResetConfirmationPublisher;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Uid\Factory\UuidFactory;
use Symfony\Component\Uid\Uuid;

final class PasswordResetConfirmationPublisherTest extends UnitTestCase
{
    private EventBusInterface&MockObject $eventBus;
    private UuidFactory&MockObject $uuidFactory;
    private PasswordResetConfirmedEventFactoryInterface&MockObject $eventFactory;
    private UserUpdatedEventFactoryInterface&MockObject $userUpdatedEventFactory;
    private PasswordResetConfirmationPublisher $publisher;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->uuidFactory = $this->createMock(UuidFactory::class);
        $this->eventFactory = $this->createMock(PasswordResetConfirmedEventFactoryInterface::class);
        $this->userUpdatedEventFactory = $this->createMock(UserUpdatedEventFactoryInterface::class);
        $this->publisher = new PasswordResetConfirmationPublisher(
            $this->eventBus,
            $this->uuidFactory,
            $this->eventFactory,
            $this->userUpdatedEventFactory
        );
    }

    public function testPublishDispatchesPasswordResetEvents(): void
    {
        $userId = $this->faker->uuid();
        $eventId = $this->faker->uuid();
        $user = $this->userWithId($userId);

        $this->expectUuidFactory($eventId);
        [$passwordResetEvent, $userUpdatedEvent] = $this->expectEvents($userId, $eventId, $user);

        $this->eventBus->expects($this->once())
            ->method('publish')
            ->with($passwordResetEvent, $userUpdatedEvent);

        $this->publisher->publish($user);
    }

    private function userWithId(string $userId): MockObject&UserInterface
    {
        $user = $this->createMock(UserInterface::class);
        $user->expects($this->once())
            ->method('getId')
            ->willReturn($userId);

        return $user;
    }

    private function expectUuidFactory(string $eventId): void
    {
        $uuid = $this->createMock(Uuid::class);
        $uuid->method('__toString')->willReturn($eventId);

        $this->uuidFactory->expects($this->once())
            ->method('create')
            ->willReturn($uuid);
    }

    /**
     * @return (MockObject&PasswordResetConfirmedEvent|MockObject&UserUpdatedEvent)[]
     *
     * @psalm-return list{MockObject&PasswordResetConfirmedEvent, MockObject&UserUpdatedEvent}
     */
    private function expectEvents(string $userId, string $eventId, UserInterface $user): array
    {
        $passwordResetEvent = $this->createMock(PasswordResetConfirmedEvent::class);
        $userUpdatedEvent = $this->createMock(UserUpdatedEvent::class);

        $this->eventFactory->expects($this->once())
            ->method('create')
            ->with($userId, $eventId)
            ->willReturn($passwordResetEvent);

        $this->userUpdatedEventFactory->expects($this->once())
            ->method('create')
            ->with($user, null, $eventId)
            ->willReturn($userUpdatedEvent);

        return [$passwordResetEvent, $userUpdatedEvent];
    }
}
