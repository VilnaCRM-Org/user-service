<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Command;

use App\Shared\Application\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\UpdateUserCommand;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\ValueObject\UserUpdate;

class UpdateUserCommandTest extends UnitTestCase
{
    private UserFactoryInterface $userFactory;
    private UuidTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = new UserFactory();
        $this->transformer = new UuidTransformer();
    }

    public function testConstructor(): void
    {
        $email = $this->faker->email();
        $initials = $this->faker->name();
        $password = $this->faker->password();

        $user = $this->userFactory->create(
            $email,
            $initials,
            $password,
            $this->transformer->transformFromString($this->faker->uuid())
        );
        $updateData = new UserUpdate(
            $email,
            $initials,
            $password,
            $password
        );

        $command = new UpdateUserCommand($user, $updateData);

        $this->assertInstanceOf(UpdateUserCommand::class, $command);
        $this->assertSame($user, $command->user);
        $this->assertSame($updateData, $command->updateData);
    }
}
