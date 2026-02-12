<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Service;

use App\Shared\Infrastructure\Factory\UuidFactory as UuidFactoryInterface;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\CommandHandler\UserUpdateApplier;
use App\User\Domain\Event\EmailChangedEvent;
use App\User\Domain\Event\PasswordChangedEvent;
use App\User\Domain\Event\UserUpdatedEvent;
use App\User\Domain\Factory\Event\EmailChangedEventFactoryInterface;
use App\User\Domain\Factory\Event\PasswordChangedEventFactoryInterface;
use App\User\Domain\Factory\Event\UserUpdatedEventFactoryInterface;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserUpdate;

final class UserUpdateApplierTest extends UnitTestCase
{
    private UserRepositoryInterface $userRepository;
    private EmailChangedEventFactoryInterface $emailChangedEventFactory;
    private PasswordChangedEventFactoryInterface $passwordChangedFactory;
    private UserUpdatedEventFactoryInterface $userUpdatedEventFactory;
    private UserFactoryInterface $userFactory;
    private UuidTransformer $uuidTransformer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->emailChangedEventFactory = $this->createMock(EmailChangedEventFactoryInterface::class);
        $this->passwordChangedFactory = $this->createMock(PasswordChangedEventFactoryInterface::class);
        $this->userUpdatedEventFactory = $this->createMock(UserUpdatedEventFactoryInterface::class);
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer(new UuidFactoryInterface());
    }

    public function testApplySavesUserAndAppendsUserUpdatedEvent(): void
    {
        $user = $this->createUser();
        $previousEmail = $user->getEmail();
        $updateData = new UserUpdate(
            $this->faker->email(),
            $this->faker->name(),
            $this->faker->password(),
            $this->faker->password()
        );
        $eventId = $this->faker->uuid();

        $emailChangedEvent = new EmailChangedEvent(
            $user->getId(),
            $updateData->newEmail,
            $previousEmail,
            $eventId
        );
        $passwordChangedEvent = new PasswordChangedEvent(
            $updateData->newEmail,
            $eventId
        );
        $userUpdatedEvent = new UserUpdatedEvent(
            $user->getId(),
            $updateData->newEmail,
            $previousEmail,
            $eventId
        );

        $this->emailChangedEventFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($emailChangedEvent);
        $this->passwordChangedFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($passwordChangedEvent);
        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($user);
        $this->userUpdatedEventFactory
            ->expects($this->once())
            ->method('create')
            ->with($user, $previousEmail, $eventId)
            ->willReturn($userUpdatedEvent);

        $events = $this->createApplier()->apply(
            $user,
            $updateData,
            $this->faker->sha256(),
            $eventId
        );

        $this->assertContains($userUpdatedEvent, $events);
        $this->assertContains($emailChangedEvent, $events);
    }

    public function testApplyPassesNullPreviousEmailWhenEmailUnchanged(): void
    {
        $user = $this->createUser();
        $password = $this->faker->password();
        $updateData = new UserUpdate(
            $user->getEmail(),
            $this->faker->name(),
            $password,
            $password
        );
        $eventId = $this->faker->uuid();
        $userUpdatedEvent = new UserUpdatedEvent(
            $user->getId(),
            $user->getEmail(),
            null,
            $eventId
        );

        $this->emailChangedEventFactory
            ->expects($this->never())
            ->method('create');
        $this->passwordChangedFactory
            ->expects($this->never())
            ->method('create');
        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($user);
        $this->userUpdatedEventFactory
            ->expects($this->once())
            ->method('create')
            ->with($user, null, $eventId)
            ->willReturn($userUpdatedEvent);

        $events = $this->createApplier()->apply(
            $user,
            $updateData,
            $this->faker->sha256(),
            $eventId
        );

        $this->assertSame([$userUpdatedEvent], $events);
    }

    private function createUser(): \App\User\Domain\Entity\User
    {
        return $this->userFactory->create(
            $this->faker->email(),
            $this->faker->name(),
            $this->faker->password(),
            $this->uuidTransformer->transformFromString($this->faker->uuid())
        );
    }

    private function createApplier(): UserUpdateApplier
    {
        return new UserUpdateApplier(
            $this->userRepository,
            $this->emailChangedEventFactory,
            $this->passwordChangedFactory,
            $this->userUpdatedEventFactory
        );
    }
}
