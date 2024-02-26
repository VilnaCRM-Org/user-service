<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Resolver;

use App\Shared\Application\Transformer\UuidTransformer;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Factory\UpdateUserCommandFactory;
use App\User\Application\Factory\UpdateUserCommandFactoryInterface;
use App\User\Application\MutationInput\MutationInputValidator;
use App\User\Application\Resolver\UserUpdateMutationResolver;
use App\User\Application\Transformer\UpdateUserMutationInputTransformer;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\ValueObject\UserUpdate;

class UserUpdateMutationResolverTest extends UnitTestCase
{
    private UserFactoryInterface $userFactory;
    private UuidTransformer $uuidTransformer;
    private UpdateUserCommandFactoryInterface $updateUserCommandFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer();
        $this->updateUserCommandFactory = new UpdateUserCommandFactory();
    }

    public function testInvoke(): void
    {
        $commandBus = $this->createMock(CommandBusInterface::class);
        $validator = $this->createMock(MutationInputValidator::class);
        $transformer = $this->createMock(UpdateUserMutationInputTransformer::class);
        $mockUpdateUserCommandFactory = $this->createMock(UpdateUserCommandFactoryInterface::class);

        $email = $this->faker->email();
        $initials = $this->faker->name();
        $password = $this->faker->password();
        $userID = $this->faker->uuid();

        $user = $this->userFactory->create(
            $email,
            $initials,
            $password,
            $this->uuidTransformer->transformFromString($userID)
        );
        $updateData = new UserUpdate($email, $initials, $password, $password);
        $command = $this->updateUserCommandFactory->create(
            $user,
            $updateData
        );

        $resolver = new UserUpdateMutationResolver(
            $commandBus,
            $validator,
            $transformer,
            $mockUpdateUserCommandFactory
        );

        $input = [
            'email' => $email,
            'initials' => $initials,
            'newPassword' => $password,
            'password' => $password,
        ];

        $transformer->expects($this->once())
            ->method('transform');

        $validator->expects($this->once())
            ->method('validate');

        $mockUpdateUserCommandFactory->expects($this->once())
            ->method('create')
            ->with($user, $updateData)
            ->willReturn($command);

        $commandBus->expects($this->once())
            ->method('dispatch')
            ->with($command);

        $result = $resolver->__invoke($user, ['args' => ['input' => $input]]);

        $this->assertSame($user, $result);
    }
}
