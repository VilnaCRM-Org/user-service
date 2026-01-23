<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Entity;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Event\EmailChangedEvent;
use App\User\Domain\Event\UserConfirmedEvent;
use App\User\Domain\Factory\ConfirmationTokenFactory;
use App\User\Domain\Factory\ConfirmationTokenFactoryInterface;
use App\User\Domain\Factory\Event\EmailChangedEventFactoryInterface;
use App\User\Domain\Factory\Event\PasswordChangedEventFactoryInterface;
use App\User\Domain\Factory\Event\UserConfirmedEventFactoryInterface;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\ValueObject\UserUpdate;

final class UserTest extends UnitTestCase
{
    private UserInterface $user;
    private UserConfirmedEventFactoryInterface $userConfirmedEventFactory;
    private EmailChangedEventFactoryInterface $emailChangedEventFactory;
    private PasswordChangedEventFactoryInterface $passwordChangedEventFactory;
    private UserFactoryInterface $userFactory;
    private ConfirmationTokenFactoryInterface $confirmationTokenFactory;
    private UuidTransformer $uuidTransformer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->userConfirmedEventFactory =
            $this->createMock(UserConfirmedEventFactoryInterface::class);
        $this->emailChangedEventFactory =
            $this->createMock(EmailChangedEventFactoryInterface::class);
        $this->passwordChangedEventFactory =
            $this->createMock(PasswordChangedEventFactoryInterface::class);
        $this->userFactory = new UserFactory();
        $this->confirmationTokenFactory = new ConfirmationTokenFactory(
            $this->faker->numberBetween(1, 10)
        );
        $this->uuidTransformer = new UuidTransformer(new UuidFactory());

        $this->user = $this->userFactory->create(
            $this->faker->email(),
            $this->faker->name(),
            $this->faker->password(),
            $this->uuidTransformer->transformFromString($this->faker->uuid())
        );
    }

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

        $expectedEmailChangedEvent = $this->createMock(EmailChangedEvent::class);

        $this->emailChangedEventFactory->expects($this->once())
            ->method('create')
            ->with($this->user, $oldEmail, $eventID)
            ->willReturn($expectedEmailChangedEvent);

        $events = $this->user->update(
            $updateData,
            $hashedNewPassword,
            $eventID,
            $this->emailChangedEventFactory,
            $this->passwordChangedEventFactory
        );

        $this->testUpdateMakeAssertions(
            $events,
            $updateData,
            $hashedNewPassword,
            $expectedEmailChangedEvent
        );
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

    /**
     * @param array<DomainEvent> $events
     */
    private function testUpdateMakeAssertions(
        array $events,
        UserUpdate $updateData,
        string $hashedNewPassword,
        EmailChangedEvent $expectedEmailChangedEvent
    ): void {
        $this->assertIsArray($events);
        $this->assertNotEmpty($events);
        $this->assertContains(
            $expectedEmailChangedEvent,
            $events,
            'EmailChangedEvent should be present in the events array'
        );
        $this->assertEquals($updateData->newEmail, $this->user->getEmail());
        $this->assertEquals(
            $updateData->newInitials,
            $this->user->getInitials()
        );
        $this->assertEquals($hashedNewPassword, $this->user->getPassword());
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
}
