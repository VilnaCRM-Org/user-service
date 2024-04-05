<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Resolver;

use App\Shared\Application\Transformer\UuidTransformer;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Factory\ConfirmUserCommandFactory;
use App\User\Application\Factory\ConfirmUserCommandFactoryInterface;
use App\User\Application\MutationInput\MutationInputValidator;
use App\User\Application\Resolver\ConfirmUserMutationResolver;
use App\User\Application\Transformer\ConfirmUserMutationInputTransformer;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Exception\TokenNotFoundException;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Factory\ConfirmationTokenFactory;
use App\User\Domain\Factory\ConfirmationTokenFactoryInterface;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\TokenRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;

final class ConfirmUserMutationResolverTest extends UnitTestCase
{
    private ConfirmationTokenFactoryInterface $confirmationTokenFactory;
    private ConfirmUserCommandFactoryInterface $confirmUserCommandFactory;
    private UserFactoryInterface $userFactory;
    private UuidTransformer $uuidTransformer;
    private TokenRepositoryInterface $tokenRepository;
    private CommandBusInterface $commandBus;
    private UserRepositoryInterface $userRepository;
    private MutationInputValidator $validator;
    private ConfirmUserMutationInputTransformer $transformer;
    private ConfirmUserCommandFactoryInterface $mockConfirmUserCommandFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->confirmationTokenFactory = new ConfirmationTokenFactory(
            $this->faker->numberBetween(1, 10)
        );
        $this->confirmUserCommandFactory = new ConfirmUserCommandFactory();
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer();

        $this->tokenRepository =
            $this->createMock(TokenRepositoryInterface::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->userRepository =
            $this->createMock(UserRepositoryInterface::class);
        $this->validator = $this->createMock(MutationInputValidator::class);
        $this->transformer = $this->createMock(
            ConfirmUserMutationInputTransformer::class
        );
        $this->mockConfirmUserCommandFactory = $this->createMock(
            ConfirmUserCommandFactoryInterface::class
        );
    }

    public function testInvoke(): void
    {
        $resolver = new ConfirmUserMutationResolver(
            $this->tokenRepository,
            $this->commandBus,
            $this->userRepository,
            $this->validator,
            $this->transformer,
            $this->mockConfirmUserCommandFactory,
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

        $this->transformer->expects($this->once())
            ->method('transform');

        $this->validator->expects($this->once())
            ->method('validate');

        $this->tokenRepository->expects($this->once())
            ->method('find')
            ->with($tokenValue)
            ->willReturn($token);

        $this->userRepository->expects($this->once())
            ->method('find')
            ->willReturn($user);

        $this->mockConfirmUserCommandFactory->expects($this->once())
            ->method('create')
            ->willReturn($command);

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($command);

        $result =
            $resolver->__invoke(null, ['args' => ['input' => $input]]);
        $this->assertInstanceOf(UserInterface::class, $result);
    }

    public function testInvokeTokenNotFound(): void
    {
        $resolver = new ConfirmUserMutationResolver(
            $this->tokenRepository,
            $this->commandBus,
            $this->userRepository,
            $this->validator,
            $this->transformer,
            $this->mockConfirmUserCommandFactory,
        );

        $tokenValue = $this->faker->uuid();
        $input = ['token' => $tokenValue];

        $this->transformer->expects($this->once())
            ->method('transform');

        $this->tokenRepository->expects($this->once())
            ->method('find')
            ->with($tokenValue)
            ->willReturn(null);

        $this->expectException(TokenNotFoundException::class);

        $resolver->__invoke(null, ['args' => ['input' => $input]]);
    }

    public function testInvokeUserNotFound(): void
    {
        $resolver = new ConfirmUserMutationResolver(
            $this->tokenRepository,
            $this->commandBus,
            $this->userRepository,
            $this->validator,
            $this->transformer,
            $this->mockConfirmUserCommandFactory,
        );

        $userID = $this->faker->uuid();

        $tokenValue = $this->faker->uuid();

        $input = ['token' => $tokenValue];
        $token = $this->confirmationTokenFactory->create($userID);

        $this->transformer->expects($this->once())
            ->method('transform');

        $this->tokenRepository->expects($this->once())
            ->method('find')
            ->with($tokenValue)
            ->willReturn($token);

        $this->userRepository->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $this->expectException(UserNotFoundException::class);

        $resolver->__invoke(null, ['args' => ['input' => $input]]);
    }
}
