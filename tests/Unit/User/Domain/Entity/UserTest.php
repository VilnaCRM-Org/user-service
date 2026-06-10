<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Entity;

use App\Shared\Domain\Collection\DomainEventCollection;
use App\User\Domain\Entity\User;
use App\User\Domain\Event\EmailChangedEvent;
use App\User\Domain\Event\PasswordChangedEvent;
use App\User\Domain\Event\UserConfirmedEvent;
use App\User\Domain\ValueObject\UserUpdate;

final class UserTest extends UserTestCase
{
    public function testNewUserIsNotConfirmedByDefault(): void
    {
        $user = $this->userFactory->create(
            $this->faker->email(),
            $this->faker->name(),
            $this->faker->password(),
            $this->uuidTransformer->transformFromString($this->faker->uuid())
        );

        $this->assertUserNotConfirmed($user);
        $this->assertConfirmedPropertyIsFalse($user);
    }

    public function testDirectConstructionStartsUnconfirmed(): void
    {
        $user = new User(
            $this->faker->email(),
            $this->faker->name(),
            $this->faker->password(),
            $this->uuidTransformer->transformFromString($this->faker->uuid())
        );

        $this->assertFalse($user->isConfirmed());
    }

    public function testConfirm(): void
    {
        $token =
            $this->confirmationTokenFactory->create($this->faker->uuid());
        $eventID = $this->faker->uuid();

        $this->userConfirmedEventFactory->expects($this->once())
            ->method('create')
            ->with($token, $eventID)
            ->willReturn(new UserConfirmedEvent($token->getTokenValue(), $eventID));

        $confirmedEvent = $this->user->confirm(
            $token,
            $eventID,
            $this->userConfirmedEventFactory
        );

        $this->assertInstanceOf(
            UserConfirmedEvent::class,
            $confirmedEvent
        );
        $this->assertTrue($this->user->isConfirmed());
    }

    public function testUpdate(): void
    {
        $oldEmail = $this->user->getEmail();
        $updateData = $this->createUpdateData();
        $hashedNewPassword = $this->faker->password();
        $eventID = $this->faker->uuid();

        $expectedEvent = $this->setupEmailChangedEventFactoryMock($oldEmail, $eventID);

        $events = $this->user->update(
            $updateData,
            $hashedNewPassword,
            $eventID,
            $this->userUpdateEventFactory
        );

        $this->makeUpdateAssertions($events, $updateData, $hashedNewPassword, $expectedEvent);
    }

    public function testUpdateEmitsPasswordChangedEventWhenPasswordDiffers(): void
    {
        $eventID = $this->faker->uuid();
        $updateData = $this->createPasswordChangeUpdate();

        $expectedEvent = $this->stubPasswordChangedFactory($eventID);

        $events = $this->user->update(
            $updateData,
            $this->faker->sha256(),
            $eventID,
            $this->userUpdateEventFactory
        );

        $this->assertContains($expectedEvent, $events->toArray());
    }

    public function testUpdateDoesNotEmitPasswordChangedEventWhenPasswordIsSame(): void
    {
        $samePassword = $this->faker->password();
        $hashedNewPassword = $this->faker->sha256();
        $eventID = $this->faker->uuid();

        $updateData = new UserUpdate(
            $this->user->getEmail(),
            $this->faker->name(),
            $samePassword,
            $samePassword,
        );

        $this->userUpdateEventFactory->expects($this->never())
            ->method('createPasswordChanged');

        $events = $this->user->update(
            $updateData,
            $hashedNewPassword,
            $eventID,
            $this->userUpdateEventFactory
        );

        $this->assertTrue($events->isEmpty());
    }

    public function testSetId(): void
    {
        $id = $this->faker->uuid();
        $this->user->setId($this->uuidTransformer->transformFromString($id));

        $this->assertEquals($id, $this->user->getId());
    }

    public function testSetEmail(): void
    {
        $email = $this->faker->email();
        $this->user->setEmail($email);

        $this->assertEquals($email, $this->user->getEmail());
    }

    public function testSetInitials(): void
    {
        $initials = $this->faker->name();
        $this->user->setInitials($initials);

        $this->assertEquals($initials, $this->user->getInitials());
    }

    public function testSetConfirmed(): void
    {
        $confirmed = true;
        $this->user->setConfirmed(true);

        $this->assertEquals($confirmed, $this->user->isConfirmed());
    }

    public function testUpgradePasswordHash(): void
    {
        $newHash = $this->faker->sha256();

        $this->user->upgradePasswordHash($newHash);

        $this->assertSame($newHash, $this->user->getPassword());
    }

    public function testSetPassword(): void
    {
        $newPassword = $this->faker->password();

        $this->user->setPassword($newPassword);

        $this->assertSame($newPassword, $this->user->getPassword());
    }

    private function assertUserNotConfirmed(User $user): void
    {
        $this->assertFalse(
            $user->isConfirmed(),
            'New user must not be confirmed'
        );
        $this->assertNotTrue(
            $user->isConfirmed(),
            'Double-check: new user is definitely not confirmed'
        );
    }

    private function assertConfirmedPropertyIsFalse(User $user): void
    {
        $reflection = new \ReflectionClass($user);
        $property = $reflection->getProperty('confirmed');
        $this->assertFalse(
            $property->getValue($user),
            'Confirmed property must be false after construction'
        );
        $this->assertSame(
            false,
            $property->getValue($user),
            'Confirmed must be exactly false (not null or other)'
        );
    }

    private function makeUpdateAssertions(
        DomainEventCollection $events,
        UserUpdate $updateData,
        string $hashedNewPassword,
        EmailChangedEvent $expectedEmailChangedEvent
    ): void {
        $this->assertInstanceOf(DomainEventCollection::class, $events);
        $this->assertFalse($events->isEmpty());
        $this->assertContains(
            $expectedEmailChangedEvent,
            $events->toArray(),
            'EmailChangedEvent should be present in the events collection'
        );
        $this->assertEquals($updateData->newEmail, $this->user->getEmail());
        $this->assertEquals(
            $updateData->newInitials,
            $this->user->getInitials()
        );
        $this->assertEquals($hashedNewPassword, $this->user->getPassword());
    }

    private function createPasswordChangeUpdate(): UserUpdate
    {
        return new UserUpdate(
            $this->user->getEmail(),
            $this->faker->name(),
            $this->faker->password(),
            $this->faker->password(),
        );
    }

    private function stubPasswordChangedFactory(
        string $eventID
    ): PasswordChangedEvent {
        $event = new PasswordChangedEvent(
            $this->user->getEmail(),
            $eventID
        );
        $this->userUpdateEventFactory->expects($this->once())
            ->method('createPasswordChanged')
            ->with($this->user->getEmail(), $eventID)
            ->willReturn($event);

        return $event;
    }

    private function createUpdateData(): UserUpdate
    {
        return new UserUpdate(
            $this->faker->email(),
            $this->faker->name(),
            $this->faker->password(),
            $this->faker->password()
        );
    }

    private function setupEmailChangedEventFactoryMock(
        string $oldEmail,
        string $eventID
    ): EmailChangedEvent {
        $expectedEvent = new EmailChangedEvent(
            (string) $this->user->getId(),
            $this->user->getEmail(),
            $oldEmail,
            $eventID
        );
        $this->userUpdateEventFactory->expects($this->once())
            ->method('createEmailChanged')
            ->with($this->user, $oldEmail, $eventID)
            ->willReturn($expectedEvent);

        return $expectedEvent;
    }
}
