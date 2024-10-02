<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Entity;

use App\Shared\Application\Transformer\UuidTransformer;
use App\Tests\Builders\ConfirmationTokenBuilder;
use App\Tests\Builders\UserBuilder;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\User;
use App\User\Domain\Event\EmailChangedEvent;
use App\User\Domain\Event\PasswordChangedEvent;
use App\User\Domain\Event\UserConfirmedEvent;
use App\User\Domain\Factory\Event\EmailChangedEventFactoryInterface;
use App\User\Domain\Factory\Event\PasswordChangedEventFactoryInterface;
use App\User\Domain\Factory\Event\UserConfirmedEventFactoryInterface;
use App\User\Domain\ValueObject\UserUpdate;
use Symfony\Component\Uid\Factory\UuidFactory;

final class UserTest extends UnitTestCase
{
    private UuidTransformer $uuidTransformer;
    private UuidFactory $uuidFactoryStub;
    private PasswordChangedEvent $passwordChangedEventStub;
    private EmailChangedEvent $emailChangedEventStub;
    private UserConfirmedEvent $userConfirmedEventStub;

    private EmailChangedEventFactoryInterface $emailChangedEventFactoryMock;
    private PasswordChangedEventFactoryInterface $passwordChangedEventFactoryMock;
    private UserConfirmedEventFactoryInterface $userConfirmedEventFactoryMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->uuidTransformer = new UuidTransformer();
        $this->uuidFactoryStub = $this->createStub(UuidFactory::class);
        $this->passwordChangedEventStub = $this->createStub(PasswordChangedEvent::class);
        $this->emailChangedEventStub = $this->createStub(EmailChangedEvent::class);
        $this->userConfirmedEventStub = $this->createStub(UserConfirmedEvent::class);

        $this->emailChangedEventFactoryMock =
            $this->createMock(EmailChangedEventFactoryInterface::class);
        $this->passwordChangedEventFactoryMock =
            $this->createMock(PasswordChangedEventFactoryInterface::class);
        $this->userConfirmedEventFactoryMock =
            $this->createMock(UserConfirmedEventFactoryInterface::class);
    }

    public function testCreate(): void
    {
        $newEmail = $this->faker->email();
        $newName = $this->faker->name();
        $newPassword = $this->faker->password();
        $newUuid = $this->uuidTransformer->transformFromString($this->faker->uuid());

        $user = new User(
            $this->faker->email(),
            $this->faker->name(),
            $this->faker->password(),
            $this->uuidTransformer->transformFromString($this->faker->uuid())
        );

        $user->setEmail($newEmail);
        $user->setInitials($newName);
        $user->setPassword($newPassword);
        $user->setId($newUuid);

        $this->assertEquals($newEmail, $user->getEmail());
        $this->assertEquals($newName, $user->getInitials());
        $this->assertEquals($newPassword, $user->getPassword());
        $this->assertEquals($newUuid, $user->getId());
    }

    public function testConfirm(): void
    {
        $user = (new UserBuilder())->build();
        $confirmationToken = (new ConfirmationTokenBuilder())->build();
        $this->uuidFactoryStub->method('create')
            ->willReturn($eventID = (new UuidFactory())->create());

        $this->userConfirmedEventFactoryMock->expects($this->once())->method('create')
            ->with($confirmationToken, (string) $eventID)
            ->willReturn($this->userConfirmedEventStub);

        $events = $user->confirm(
            $confirmationToken,
            $this->uuidFactoryStub,
            $this->userConfirmedEventFactoryMock
        );

        $this->assertTrue($user->isConfirmed());
        $this->assertSame($this->userConfirmedEventStub, $events);
    }

    public function testUpdate(): void
    {
        $user = (new UserBuilder())
            ->withEmail('old-email')
            ->withInitials('old-initials')
            ->withPassword('old-password')
            ->build();
        $userUpdateDto = new UserUpdate(
            'new-email',
            'new-initials',
            'new-password',
            'old-password'
        );
        $this->uuidFactoryStub->method('create')
            ->willReturnOnConsecutiveCalls(
                $firstEventID = (new UuidFactory())->create(),
                $secondEventID = (new UuidFactory())->create()
            );

        $this->emailChangedEventFactoryMock->expects($this->once())->method('create')
            ->with($user, (string) $firstEventID)
            ->willReturn($this->emailChangedEventStub);
        $this->passwordChangedEventFactoryMock->expects($this->once())->method('create')
            ->with($userUpdateDto->newEmail, (string) $secondEventID)
            ->willReturn($this->passwordChangedEventStub);

        $events = $user->update(
            $userUpdateDto,
            'new-hashed-password',
            $this->uuidFactoryStub,
            $this->emailChangedEventFactoryMock,
            $this->passwordChangedEventFactoryMock
        );

        $this->assertEquals([$this->emailChangedEventStub, $this->passwordChangedEventStub], $events);
        $this->assertEquals($userUpdateDto->newEmail, $user->getEmail());
        $this->assertEquals($userUpdateDto->newInitials, $user->getInitials());
        $this->assertEquals('new-hashed-password', $user->getPassword());
    }

    public function testUpdatePassword(): void
    {
        $oldPassword = $this->faker->password();
        $newPassword = $this->faker->password();
        $user = (new UserBuilder())
            ->withPassword($oldPassword)
            ->build();
        $this->uuidFactoryStub->method('create')
            ->willReturn($eventID = (new UuidFactory())->create());

        $this->passwordChangedEventFactoryMock->expects($this->once())->method('create')
            ->with($user->getEmail(), $eventID)
            ->willReturn($this->createStub(PasswordChangedEvent::class));

        $events = $user->updatePassword(
            $newPassword,
            $this->uuidFactoryStub,
            $this->passwordChangedEventFactoryMock
        );

        $this->assertInstanceOf(PasswordChangedEvent::class, $events[0]);
        $this->assertEquals($newPassword, $user->getPassword());
    }
}
