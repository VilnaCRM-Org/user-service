<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Entity\ConfirmationToken;
use App\User\Domain\Entity\User;
use App\User\Domain\Event\UserConfirmedEvent;
use App\User\Domain\Factory\Event\EmailChangedEventFactoryInterface;
use App\User\Domain\Factory\Event\PasswordChangedEventFactoryInterface;
use App\User\Domain\Factory\Event\UserConfirmedEventFactoryInterface;
use App\User\Domain\ValueObject\UserUpdateData;
use DG\BypassFinals;
use Faker\Factory;
use Faker\Generator;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    private User $user;
    private UserConfirmedEventFactoryInterface $userConfirmedEventFactory;
    private EmailChangedEventFactoryInterface $emailChangedEventFactory;
    private PasswordChangedEventFactoryInterface $passwordChangedEventFactory;
    private Generator $faker;

    protected function setUp(): void
    {
        BypassFinals::enable(bypassReadOnly: false);
        parent::setUp();

        $this->faker = Factory::create();

        $this->userConfirmedEventFactory = $this->createMock(
            UserConfirmedEventFactoryInterface::class
        );
        $this->emailChangedEventFactory = $this->createMock(
            EmailChangedEventFactoryInterface::class
        );
        $this->passwordChangedEventFactory = $this->createMock(
            PasswordChangedEventFactoryInterface::class
        );

        $this->user = new User(
            $this->faker->email(),
            $this->faker->name(),
            $this->faker->password(),
            new Uuid($this->faker->uuid())
        );
    }

    public function testConfirm(): void
    {
        $token = new ConfirmationToken(
            $this->faker->uuid(),
            $this->user->getId()
        );
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
        $updateData = new UserUpdateData(
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

        $this->assertIsArray($events);
        $this->assertNotEmpty($events);
        $this->assertEquals($updateData->newEmail, $this->user->getEmail());
        $this->assertEquals($updateData->newInitials, $this->user->getInitials());
        $this->assertEquals($hashedNewPassword, $this->user->getPassword());
    }
}
