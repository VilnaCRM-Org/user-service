<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Entity;

use App\Shared\Application\Transformer\UuidTransformer;
use App\Tests\Builders\UserBuilder;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\User;
use App\User\Domain\Event\PasswordChangedEvent;
use App\User\Domain\Factory\Event\PasswordChangedEventFactoryInterface;

final class UserTest extends UnitTestCase
{
    private UuidTransformer $uuidTransformer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->uuidTransformer = new UuidTransformer();
    }

    public function testCreate(): void
    {
        $newEmail = $this->faker->email();
        $newName = $this->faker->name();
        $newPassword = $this->faker->password;
        $newUuid = $this->uuidTransformer->transformFromString($this->faker->uuid());

        $user = new User(
            $this->faker->email(),
            $this->faker->name(),
            $this->faker->password,
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

    public function testUpdatePassword(): void
    {
        $oldPassword = $this->faker->password();
        $newPassword = $this->faker->password();

        $user = (new UserBuilder())
            ->withPassword($oldPassword)
            ->build();

        $passwordChangedEventFactoryMock = $this->createStub(PasswordChangedEventFactoryInterface::class);
        $passwordChangedEventFactoryMock->method('create')
            ->willReturn($this->createStub(PasswordChangedEvent::class));

        $events = $user->updatePassword(
            $newPassword,
            $this->faker->uuid(),
            $passwordChangedEventFactoryMock
        );

        $this->assertInstanceOf(PasswordChangedEvent::class, $events[0]);
        $this->assertEquals($newPassword, $user->getPassword());
    }
}
