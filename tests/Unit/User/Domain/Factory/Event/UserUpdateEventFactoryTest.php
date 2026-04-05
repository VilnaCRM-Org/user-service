<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Factory\Event;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Event\EmailChangedEvent;
use App\User\Domain\Event\PasswordChangedEvent;
use App\User\Domain\Event\UserUpdatedEvent;
use App\User\Domain\Factory\Event\EmailChangedEventFactoryInterface;
use App\User\Domain\Factory\Event\PasswordChangedEventFactoryInterface;
use App\User\Domain\Factory\Event\UserUpdatedEventFactoryInterface;
use App\User\Domain\Factory\Event\UserUpdateEventFactory;
use App\User\Domain\Factory\Event\UserUpdateEventFactoryInterface;
use PHPUnit\Framework\MockObject\MockObject;

final class UserUpdateEventFactoryTest extends UnitTestCase
{
    private EmailChangedEventFactoryInterface&MockObject $emailChangedEventFactory;
    private PasswordChangedEventFactoryInterface&MockObject $passwordChangedEventFactory;
    private UserUpdatedEventFactoryInterface&MockObject $userUpdatedEventFactory;
    private UserUpdateEventFactoryInterface $factory;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->emailChangedEventFactory = $this->createMock(
            EmailChangedEventFactoryInterface::class
        );
        $this->passwordChangedEventFactory = $this->createMock(
            PasswordChangedEventFactoryInterface::class
        );
        $this->userUpdatedEventFactory = $this->createMock(UserUpdatedEventFactoryInterface::class);
        $this->factory = new UserUpdateEventFactory(
            $this->emailChangedEventFactory,
            $this->passwordChangedEventFactory,
            $this->userUpdatedEventFactory
        );
    }

    public function testCreateEmailChangedDelegatesToConcreteFactory(): void
    {
        $user = $this->createMock(UserInterface::class);
        $oldEmail = $this->faker->email();
        $eventId = $this->faker->uuid();
        $event = $this->createMock(EmailChangedEvent::class);

        $this->emailChangedEventFactory->expects($this->once())
            ->method('create')
            ->with($user, $oldEmail, $eventId)
            ->willReturn($event);

        $this->assertSame(
            $event,
            $this->factory->createEmailChanged($user, $oldEmail, $eventId)
        );
    }

    public function testCreatePasswordChangedDelegatesToConcreteFactory(): void
    {
        $email = $this->faker->email();
        $eventId = $this->faker->uuid();
        $event = $this->createMock(PasswordChangedEvent::class);

        $this->passwordChangedEventFactory->expects($this->once())
            ->method('create')
            ->with($email, $eventId)
            ->willReturn($event);

        $this->assertSame(
            $event,
            $this->factory->createPasswordChanged($email, $eventId)
        );
    }

    public function testCreateUserUpdatedDelegatesToConcreteFactory(): void
    {
        $user = $this->createMock(UserInterface::class);
        $previousEmail = $this->faker->email();
        $eventId = $this->faker->uuid();
        $event = $this->createMock(UserUpdatedEvent::class);

        $this->userUpdatedEventFactory->expects($this->once())
            ->method('create')
            ->with($user, $previousEmail, $eventId)
            ->willReturn($event);

        $this->assertSame(
            $event,
            $this->factory->createUserUpdated($user, $previousEmail, $eventId)
        );
    }
}
