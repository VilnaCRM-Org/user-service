<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Entity;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\UserInterface;
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

    public function testConfirm(): void
    {
        $token =
            $this->confirmationTokenFactory->create($this->faker->uuid());
        $eventID = $this->faker->uuid();

        $this->userConfirmedEventFactory->expects($this->once())
            ->method('create')
            ->with($token, $eventID)
            ->willReturn(new UserConfirmedEvent($token, $eventID));

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
        $updateData = new UserUpdate(
            $this->faker->email(),
            $this->faker->password(),
            $this->faker->password(),
            $this->faker->name()
        );
        $hashedNewPassword = $this->faker->password();
        $eventID = $this->faker->uuid();

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
            $hashedNewPassword
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

    /**
     * @param array<DomainEvent> $events
     */
    private function testUpdateMakeAssertions(
        array $events,
        UserUpdate $updateData,
        string $hashedNewPassword
    ): void {
        $this->assertIsArray($events);
        $this->assertNotEmpty($events);
        $this->assertEquals($updateData->newEmail, $this->user->getEmail());
        $this->assertEquals(
            $updateData->newInitials,
            $this->user->getInitials()
        );
        $this->assertEquals($hashedNewPassword, $this->user->getPassword());
    }
}
