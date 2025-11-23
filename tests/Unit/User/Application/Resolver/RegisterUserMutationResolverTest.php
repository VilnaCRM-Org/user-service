<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Resolver;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\RegisterUserCommandResponse;
use App\User\Application\Factory\SignUpCommandFactory;
use App\User\Application\Factory\SignUpCommandFactoryInterface;
use App\User\Application\MutationInput\MutationInputValidator;
use App\User\Application\Resolver\RegisterUserMutationResolver;
use App\User\Application\Transformer\CreateUserMutationInputTransformer;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;

final class RegisterUserMutationResolverTest extends UnitTestCase
{
    private SignUpCommandFactoryInterface $signUpCommandFactory;
    private UserFactoryInterface $userFactory;
    private UuidTransformer $uuidTransformer;
    private SignUpCommandFactoryInterface $mockSignUpCommandFactory;
    private CommandBusInterface $commandBus;
    private MutationInputValidator $validator;
    private CreateUserMutationInputTransformer $transformer;
    private RegisterUserMutationResolver $resolver;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->signUpCommandFactory = new SignUpCommandFactory();
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer(new UuidFactory());
        $this->mockSignUpCommandFactory =
            $this->createMock(SignUpCommandFactoryInterface::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->validator = $this->createMock(MutationInputValidator::class);
        $this->transformer =
            $this->createMock(CreateUserMutationInputTransformer::class);
        $this->resolver = new RegisterUserMutationResolver(
            $this->commandBus,
            $this->validator,
            $this->transformer,
            $this->mockSignUpCommandFactory
        );
    }

    public function testInvoke(): void
    {
        $email = $this->faker->email();
        $initials = $this->faker->name();
        $password = $this->faker->password();
        $input = [
            'email' => $email,
            'initials' => $initials,
            'password' => $password,
        ];

        $this->setExpectations($email, $initials, $password);

        $result = $this->resolver->__invoke(
            null,
            ['args' => ['input' => $input]]
        );

        $this->assertInstanceOf(UserInterface::class, $result);
    }

    private function setExpectations(
        string $email,
        string $initials,
        string $password,
    ): void {
        $user = $this->userFactory->create(
            $email,
            $initials,
            $password,
            $this->uuidTransformer->transformFromString($this->faker->uuid())
        );
        $command =
            $this->signUpCommandFactory->create($email, $initials, $password);
        $command->setResponse(new RegisterUserCommandResponse($user));

        $this->transformer->expects($this->once())
            ->method('transform');

        $this->validator->expects($this->once())
            ->method('validate');

        $this->mockSignUpCommandFactory->expects($this->once())
            ->method('create')
            ->with($email, $initials, $password)
            ->willReturn($command);

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($command);
    }
}
