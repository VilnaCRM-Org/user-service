<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Resolver;

use App\Shared\Application\Transformer\UuidTransformer;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\SignUpCommandResponse;
use App\User\Application\Factory\SignUpCommandFactory;
use App\User\Application\Factory\SignUpCommandFactoryInterface;
use App\User\Application\MutationInput\MutationInputValidator;
use App\User\Application\Resolver\RegisterUserMutationResolver;
use App\User\Application\Transformer\CreateUserMutationInputTransformer;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;

class RegisterUserMutationResolverTest extends UnitTestCase
{
    private SignUpCommandFactoryInterface $signUpCommandFactory;
    private UserFactoryInterface $userFactory;
    private UuidTransformer $uuidTransformer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->signUpCommandFactory = new SignUpCommandFactory();
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer();
    }

    public function testInvoke(): void
    {
        $mockSignUpCommandFactory = $this->createMock(SignUpCommandFactoryInterface::class);
        $commandBus = $this->createMock(CommandBusInterface::class);
        $validator = $this->createMock(MutationInputValidator::class);
        $transformer = $this->createMock(CreateUserMutationInputTransformer::class);

        $email = $this->faker->email();
        $initials = $this->faker->name();
        $password = $this->faker->password();
        $input = [
            'email' => $email,
            'initials' => $initials,
            'password' => $password,
        ];

        $userID = $this->faker->uuid();

        $user = $this->userFactory->create(
            $email,
            $initials,
            $password,
            $this->uuidTransformer->transformFromString($userID)
        );
        $command = $this->signUpCommandFactory->create($email, $initials, $password);
        $command->setResponse(new SignUpCommandResponse($user));

        $transformer->expects($this->once())
            ->method('transform');

        $validator->expects($this->once())
            ->method('validate');

        $mockSignUpCommandFactory->expects($this->once())
            ->method('create')
            ->with($email, $initials, $password)
            ->willReturn($command);

        $commandBus->expects($this->once())
            ->method('dispatch')
            ->with($command);

        $resolver = new RegisterUserMutationResolver(
            $commandBus,
            $validator,
            $transformer,
            $mockSignUpCommandFactory
        );

        $result = $resolver->__invoke(null, ['args' => ['input' => $input]]);

        $this->assertInstanceOf(UserInterface::class, $result);
    }
}
