<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Service;

use App\Shared\Infrastructure\Factory\UuidFactory as UuidFactoryInterface;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Service\UserUpdateApplier;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserInterface;
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
        $this->emailChangedEventFactory = $this->createMock(
            EmailChangedEventFactoryInterface::class
        );
        $this->passwordChangedFactory = $this->createMock(
            PasswordChangedEventFactoryInterface::class
        );
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
        [$u, $e] = $this->expectUpdateWithEmailChange($user, $updateData, $previousEmail, $eventId);
        $hash = $this->faker->sha256();
        $events = $this->createApplier()->apply($user, $updateData, $hash, $eventId);
        $this->assertContains($u, $events);
        $this->assertContains($e, $events);
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
        $userUpdated = $this->expectUpdateWithoutEmailChange($user, $eventId);
        $events = $this->createApplier()->apply(
            $user,
            $updateData,
            $this->faker->sha256(),
            $eventId
        );
        $this->assertSame([$userUpdated], $events);
    }

    /**
     * @return array{UserUpdatedEvent, EmailChangedEvent}
     */
    private function expectUpdateWithEmailChange(
        UserInterface $user,
        UserUpdate $updateData,
        string $previousEmail,
        string $eventId
    ): array {
        $uid = $user->getId();
        $newEmail = $updateData->newEmail;
        $emailChanged = new EmailChangedEvent($uid, $newEmail, $previousEmail, $eventId);
        $passwordChanged = new PasswordChangedEvent($newEmail, $eventId);
        $userUpdated = new UserUpdatedEvent($uid, $newEmail, $previousEmail, $eventId);
        $this->emailChangedEventFactory
            ->expects($this->once())
            ->method('create')->willReturn($emailChanged);
        $this->passwordChangedFactory
            ->expects($this->once())
            ->method('create')->willReturn($passwordChanged);
        $this->expectSaveUser($user);
        $this->expectUserUpdatedEvent($user, $previousEmail, $eventId, $userUpdated);
        return [$userUpdated, $emailChanged];
    }

    private function expectUpdateWithoutEmailChange(
        UserInterface $user,
        string $eventId
    ): UserUpdatedEvent {
        $userUpdated = new UserUpdatedEvent(
            $user->getId(),
            $user->getEmail(),
            null,
            $eventId
        );
        $this->emailChangedEventFactory->expects($this->never())->method('create');
        $this->passwordChangedFactory->expects($this->never())->method('create');
        $this->expectSaveUser($user);
        $this->expectUserUpdatedEvent($user, null, $eventId, $userUpdated);

        return $userUpdated;
    }

    private function expectSaveUser(UserInterface $user): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($user);
    }

    private function expectUserUpdatedEvent(
        UserInterface $user,
        ?string $previousEmail,
        string $eventId,
        UserUpdatedEvent $userUpdated
    ): void {
        $this->userUpdatedEventFactory
            ->expects($this->once())
            ->method('create')
            ->with($user, $previousEmail, $eventId)
            ->willReturn($userUpdated);
    }

    private function createUser(): User
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
