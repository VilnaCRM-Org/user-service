<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Factory;

use App\Shared\Application\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\UpdateUserCommand;
use App\User\Application\Factory\UpdateUserCommandFactory;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\ValueObject\UserUpdate;

class UpdateUserCommandFactoryTest extends UnitTestCase
{
    private UserFactoryInterface $userFactory;
    private UuidTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = new UserFactory();
        $this->transformer = new UuidTransformer();
    }

    public function testCreate(): void
    {
        $factory = new UpdateUserCommandFactory();
        $user = $this->userFactory->create(
            $this->faker->email(),
            $this->faker->name(),
            $this->faker->password(),
            $this->transformer->transformFromString($this->faker->uuid())
        );
        $updateData = new UserUpdate(
            $this->faker->email(),
            $this->faker->name(),
            $this->faker->password(),
            $this->faker->password()
        );

        $command = $factory->create($user, $updateData);

        $this->assertInstanceOf(UpdateUserCommand::class, $command);
    }
}
