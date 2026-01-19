<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Factory;

use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\DeleteUserCommand;
use App\User\Application\Factory\DeleteUserCommandFactory;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;

final class DeleteUserCommandFactoryTest extends UnitTestCase
{
    private UserFactoryInterface $userFactory;
    private UuidTransformer $transformer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = new UserFactory();
        $this->transformer = new UuidTransformer(new UuidFactory());
    }

    public function testCreate(): void
    {
        $factory = new DeleteUserCommandFactory();
        $user = $this->userFactory->create(
            $this->faker->email(),
            $this->faker->name(),
            $this->faker->password(),
            $this->transformer->transformFromString($this->faker->uuid())
        );

        $command = $factory->create($user);

        $this->assertInstanceOf(DeleteUserCommand::class, $command);
        $this->assertSame($user, $command->user);
    }
}
