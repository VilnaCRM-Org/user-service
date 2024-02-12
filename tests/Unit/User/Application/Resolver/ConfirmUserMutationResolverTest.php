<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Resolver;

use App\Shared\Application\Transformer\UuidTransformer;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Exception\TokenNotFoundException;
use App\User\Application\Exception\UserNotFoundException;
use App\User\Application\Factory\ConfirmUserCommandFactory;
use App\User\Application\Factory\ConfirmUserCommandFactoryInterface;
use App\User\Application\MutationInput\MutationInputValidator;
use App\User\Application\Resolver\ConfirmUserMutationResolver;
use App\User\Application\Transformer\ConfirmUserMutationInputTransformer;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Factory\ConfirmationTokenFactory;
use App\User\Domain\Factory\ConfirmationTokenFactoryInterface;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\TokenRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;

class ConfirmUserMutationResolverTest extends UnitTestCase
{
    private ConfirmationTokenFactoryInterface $confirmationTokenFactory;
    private ConfirmUserCommandFactoryInterface $confirmUserCommandFactory;
    private UserFactoryInterface $userFactory;
    private UuidTransformer $uuidTransformer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->confirmationTokenFactory = new ConfirmationTokenFactory($this->faker->numberBetween(1, 10));
        $this->confirmUserCommandFactory = new ConfirmUserCommandFactory();
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer();
    }

    public function testInvoke(): void
    {
        $tokenRepository = $this->createMock(TokenRepositoryInterface::class);
        $commandBus = $this->createMock(CommandBusInterface::class);
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $validator = $this->createMock(MutationInputValidator::class);
        $transformer = $this->createMock(ConfirmUserMutationInputTransformer::class);
        $mockConfirmUserCommandFactory = $this->createMock(ConfirmUserCommandFactoryInterface::class);

        $resolver = new ConfirmUserMutationResolver(
            $tokenRepository,
            $commandBus,
            $userRepository,
            $validator,
            $transformer,
            $mockConfirmUserCommandFactory
        );

        $userID = $this->faker->uuid();
        $email = $this->faker->email();
        $initials = $this->faker->name();
        $password = $this->faker->password();

        $user = $this->userFactory->create(
            $email,
            $initials,
            $password,
            $this->uuidTransformer->transformFromString($userID)
        );

        $tokenValue = $this->faker->uuid();

        $input = ['token' => $tokenValue];
        $token = $this->confirmationTokenFactory->create($userID);

        $command = $this->confirmUserCommandFactory->create($token);

        $transformer->expects($this->once())
            ->method('transform');

        $validator->expects($this->once())
            ->method('validate');

        $tokenRepository->expects($this->once())
            ->method('find')
            ->with($tokenValue)
            ->willReturn($token);

        $userRepository->expects($this->once())
            ->method('find')
            ->willReturn($user);

        $mockConfirmUserCommandFactory->expects($this->once())
            ->method('create')
            ->willReturn($command);

        $commandBus->expects($this->once())
            ->method('dispatch')
            ->with($command);

        $result = $resolver->__invoke(null, ['args' => ['input' => $input]]);
        $this->assertInstanceOf(UserInterface::class, $result);
    }

    public function testInvokeTokenNotFound(): void
    {
        $tokenRepository = $this->createMock(TokenRepositoryInterface::class);
        $commandBus = $this->createMock(CommandBusInterface::class);
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $validator = $this->createMock(MutationInputValidator::class);
        $transformer = $this->createMock(ConfirmUserMutationInputTransformer::class);
        $confirmUserCommandFactory = $this->createMock(ConfirmUserCommandFactoryInterface::class);

        $resolver = new ConfirmUserMutationResolver(
            $tokenRepository,
            $commandBus,
            $userRepository,
            $validator,
            $transformer,
            $confirmUserCommandFactory
        );

        $tokenValue = $this->faker->uuid();
        $input = ['token' => $tokenValue];

        $transformer->expects($this->once())
            ->method('transform');

        $tokenRepository->expects($this->once())
            ->method('find')
            ->with($tokenValue)
            ->willReturn(null);

        $this->expectException(TokenNotFoundException::class);

        $resolver->__invoke(null, ['args' => ['input' => $input]]);
    }

    public function testInvokeUserNotFound(): void
    {
        $tokenRepository = $this->createMock(TokenRepositoryInterface::class);
        $commandBus = $this->createMock(CommandBusInterface::class);
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $validator = $this->createMock(MutationInputValidator::class);
        $transformer = $this->createMock(ConfirmUserMutationInputTransformer::class);
        $confirmUserCommandFactory = $this->createMock(ConfirmUserCommandFactoryInterface::class);

        $resolver = new ConfirmUserMutationResolver(
            $tokenRepository,
            $commandBus,
            $userRepository,
            $validator,
            $transformer,
            $confirmUserCommandFactory
        );

        $userID = $this->faker->uuid();

        $tokenValue = $this->faker->uuid();

        $input = ['token' => $tokenValue];
        $token = $this->confirmationTokenFactory->create($userID);

        $transformer->expects($this->once())
            ->method('transform');

        $tokenRepository->expects($this->once())
            ->method('find')
            ->with($tokenValue)
            ->willReturn($token);

        $userRepository->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $this->expectException(UserNotFoundException::class);

        $resolver->__invoke(null, ['args' => ['input' => $input]]);
    }
}
