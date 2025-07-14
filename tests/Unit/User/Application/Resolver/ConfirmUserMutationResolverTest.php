<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Resolver;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Factory\ConfirmUserCommandFactory;
use App\User\Application\Factory\ConfirmUserCommandFactoryInterface;
use App\User\Application\MutationInput\MutationInputValidator;
use App\User\Application\Query\GetUserQueryHandler;
use App\User\Application\Resolver\ConfirmUserMutationResolver;
use App\User\Application\Transformer\ConfirmUserMutationInputTransformer;
use App\User\Domain\Entity\ConfirmationTokenInterface;
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
    private GetUserQueryHandler $getUserQueryHandler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->initFactories();
        $this->tokenRepository = $this->createMock(
            TokenRepositoryInterface::class
        );
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->userRepository = $this->createMock(
            UserRepositoryInterface::class
        );
        $this->validator = $this->createMock(MutationInputValidator::class);
        $this->transformer = $this->createMock(
            ConfirmUserMutationInputTransformer::class
        );
        $this->mockConfirmUserCommandFactory = $this->createMock(
            ConfirmUserCommandFactoryInterface::class
        );
        $this->getUserQueryHandler = $this->createMock(
            GetUserQueryHandler::class
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

        $token = $this->confirmationTokenFactory->create($user->getID());
        $tokenValue = $token->getTokenValue();

        $this->testInvokeSetExpectations($user, $token);

        $input = ['token' => $tokenValue];

        $result = $this->getResolver()->__invoke(
            null,
            ['args' => ['input' => $input]]
        );
        $this->assertInstanceOf(UserInterface::class, $result);
    }

    public function testInvokeTokenNotFound(): void
    {
        $tokenValue = $this->faker->uuid();
        $input = ['token' => $tokenValue];

        $this->transformer->expects($this->once())
            ->method('transform');

        $this->tokenRepository->expects($this->once())
            ->method('find')
            ->with($tokenValue)
            ->willReturn(null);

        $this->expectException(TokenNotFoundException::class);

        $this->getResolver()->__invoke(
            null,
            ['args' => ['input' => $input]]
        );
    }

    public function testInvokeUserNotFound(): void
    {
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

        $this->getUserQueryHandler->expects($this->once())
            ->method('handle')
            ->with($userID)
            ->willThrowException(new UserNotFoundException());

        $this->expectException(UserNotFoundException::class);

        $this->getResolver()->__invoke(
            null,
            ['args' => ['input' => $input]]
        );
    }

    private function getResolver(): ConfirmUserMutationResolver
    {
        return new ConfirmUserMutationResolver(
            $this->tokenRepository,
            $this->commandBus,
            $this->getUserQueryHandler,
            $this->validator,
            $this->transformer,
            $this->mockConfirmUserCommandFactory,
        );
    }

    private function testInvokeSetExpectations(
        UserInterface $user,
        ConfirmationTokenInterface $token
    ): void {
        $command = $this->confirmUserCommandFactory->create($token);

        $this->transformer->expects($this->once())
            ->method('transform');

        $this->validator->expects($this->once())
            ->method('validate');

        $this->tokenRepository->expects($this->once())
            ->method('find')
            ->with($token->getTokenValue())
            ->willReturn($token);

        $this->getUserQueryHandler->expects($this->once())
            ->method('handle')
            ->with($token->getUserId())
            ->willReturn($user);

        $this->mockConfirmUserCommandFactory->expects($this->once())
            ->method('create')
            ->willReturn($command);

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($command);
    }

    private function initFactories(): void
    {
        $this->confirmationTokenFactory = new ConfirmationTokenFactory(
            $this->faker->numberBetween(1, 10)
        );
        $this->confirmUserCommandFactory = new ConfirmUserCommandFactory();
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer(new UuidFactory());
    }
}
