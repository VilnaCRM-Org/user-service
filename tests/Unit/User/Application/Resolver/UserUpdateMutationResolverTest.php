<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Resolver;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Factory\UpdateUserCommandFactory;
use App\User\Application\Factory\UpdateUserCommandFactoryInterface;
use App\User\Application\Resolver\UserUpdateMutationResolver;
use App\User\Application\Transformer\UpdateUserMutationInputTransformer;
use App\User\Application\Validator\MutationInputValidator;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\ValueObject\UserUpdate;

final class UserUpdateMutationResolverTest extends UnitTestCase
{
    private UserFactoryInterface $userFactory;
    private UuidTransformer $uuidTransformer;
    private UpdateUserCommandFactoryInterface $updateUserCommandFactory;
    private CommandBusInterface $commandBus;
    private MutationInputValidator $validator;
    private UpdateUserMutationInputTransformer $transformer;
    private UpdateUserCommandFactoryInterface $mockUpdateUserCommandFactory;
    private UserUpdateMutationResolver $resolver;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer(new UuidFactory());
        $this->updateUserCommandFactory = new UpdateUserCommandFactory();
        $this->commandBus =
            $this->createMock(CommandBusInterface::class);
        $this->validator =
            $this->createMock(MutationInputValidator::class);
        $this->transformer =
            $this->createMock(UpdateUserMutationInputTransformer::class);
        $this->mockUpdateUserCommandFactory =
            $this->createMock(UpdateUserCommandFactoryInterface::class);
        $this->resolver = new UserUpdateMutationResolver(
            $this->commandBus,
            $this->validator,
            $this->transformer,
            $this->mockUpdateUserCommandFactory
        );
    }

    public function testInvoke(): void
    {
        $email = $this->faker->email();
        $initials = $this->faker->name();
        $password = $this->faker->password();

        $user = $this->userFactory->create(
            $email,
            $initials,
            $password,
            $this->uuidTransformer->transformFromString($this->faker->uuid())
        );
        $this->prepareExpectations($user, $email, $initials, $password);

        $input = [
            'email' => $email,
            'initials' => $initials,
            'newPassword' => $password,
            'password' => $password,
        ];

        $this->assertSame(
            $user,
            $this->resolver->__invoke($user, ['args' => ['input' => $input]]),
        );
    }

    private function prepareExpectations(
        UserInterface $user,
        string $email,
        string $initials,
        string $password
    ): void {
        $updateData = new UserUpdate($email, $initials, $password, $password);
        $command = $this->updateUserCommandFactory->create(
            $user,
            $updateData
        );

        $this->transformer->expects($this->once())
            ->method('transform');

        $this->validator->expects($this->once())
            ->method('validate');

        $this->mockUpdateUserCommandFactory->expects($this->once())
            ->method('create')
            ->with($user, $updateData)
            ->willReturn($command);

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($command);
    }
}
