<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Resolver;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\Tests\Unit\User\Application\Support\RegisterUserCommandExpectationHelper;
use App\User\Application\Factory\SignUpCommandFactory;
use App\User\Application\Factory\SignUpCommandFactoryInterface;
use App\User\Application\MutationInput\CreateUserMutationInput;
use App\User\Application\Query\FindUserByEmailQueryHandlerInterface;
use App\User\Application\Resolver\RegisterUserMutationResolver;
use App\User\Application\Transformer\CreateUserMutationInputTransformer;
use App\User\Application\Validator\MutationInputValidator;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;
use PHPUnit\Framework\MockObject\MockObject;

final class RegisterUserMutationResolverTest extends UnitTestCase
{
    private SignUpCommandFactoryInterface $signUpCommandFactory;
    private UserFactoryInterface $userFactory;
    private UuidTransformer $uuidTransformer;
    private SignUpCommandFactoryInterface&MockObject $mockSignUpCommandFactory;
    private CommandBusInterface&MockObject $commandBus;
    private MutationInputValidator&MockObject $validator;
    private CreateUserMutationInputTransformer&MockObject $transformer;
    private FindUserByEmailQueryHandlerInterface&MockObject $findUserByEmailQueryHandler;
    private RegisterUserCommandExpectationHelper $commandExpectationHelper;
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
        $this->findUserByEmailQueryHandler = $this->createMock(
            FindUserByEmailQueryHandlerInterface::class
        );
        $this->commandExpectationHelper =
            new RegisterUserCommandExpectationHelper(
                $this->findUserByEmailQueryHandler,
                $this->mockSignUpCommandFactory,
                $this->commandBus
            );
        $this->resolver = new RegisterUserMutationResolver(
            $this->commandBus,
            $this->validator,
            $this->transformer,
            $this->mockSignUpCommandFactory,
            $this->findUserByEmailQueryHandler
        );
    }

    public function testInvokeReturnsExistingUserWithoutDispatch(): void
    {
        $email = $this->faker->email();
        $input = $this->createInput($email);
        $user = $this->createUserFromInput($input);

        $this->setValidationExpectations($input);
        $this->findUserByEmailQueryHandler->expects($this->once())
            ->method('find')
            ->with($email)
            ->willReturn($user);
        $this->mockSignUpCommandFactory->expects($this->never())
            ->method('create');
        $this->commandBus->expects($this->never())
            ->method('dispatch');

        $result = $this->resolver->__invoke(
            null,
            ['args' => ['input' => $input]]
        );

        $this->assertSame($user, $result);
    }

    public function testInvokeDispatchesRegistrationAndReturnsCreatedUser(): void
    {
        $email = $this->faker->email();
        $input = $this->createInput($email);
        $user = $this->createUserFromInput($input);
        $command = $this->signUpCommandFactory->create(
            $email,
            $input['initials'],
            $input['password']
        );

        $this->setValidationExpectations($input);
        $this->commandExpectationHelper->expectRegistration(
            $email,
            $input['initials'],
            $input['password'],
            $command,
            $user
        );

        $result = $this->resolver->__invoke(
            null,
            ['args' => ['input' => $input]]
        );

        $this->assertSame($user, $result);
    }

    public function testInvokeThrowsWhenCreatedUserCannotBeLoaded(): void
    {
        $email = $this->faker->email();
        $input = $this->createInput($email);
        $command = $this->signUpCommandFactory->create(
            $email,
            $input['initials'],
            $input['password']
        );

        $this->expectException(UserNotFoundException::class);
        $this->setValidationExpectations($input);
        $this->commandExpectationHelper->expectMissingCreatedUser(
            $email,
            $command
        );

        $this->resolver->__invoke(null, ['args' => ['input' => $input]]);
    }

    public function testInvokeValidatesBeforeReadingRequiredEmail(): void
    {
        $input = [
            'initials' => $this->faker->name(),
            'password' => $this->faker->password(),
        ];
        $transformedInput = new CreateUserMutationInput(
            null,
            $input['initials'],
            $input['password']
        );
        $validationFailure = new \RuntimeException('Invalid input.');

        $this->transformer->expects($this->once())
            ->method('transform')
            ->with($input)
            ->willReturn($transformedInput);
        $this->validator->expects($this->once())
            ->method('validate')
            ->with($transformedInput)
            ->willThrowException($validationFailure);
        $this->findUserByEmailQueryHandler->expects($this->never())
            ->method('find');
        $this->mockSignUpCommandFactory->expects($this->never())
            ->method('create');
        $this->commandBus->expects($this->never())
            ->method('dispatch');

        $this->expectExceptionObject($validationFailure);

        $this->resolver->__invoke(null, ['args' => ['input' => $input]]);
    }

    /**
     * @return array{email:string,initials:string,password:string}
     */
    private function createInput(string $email): array
    {
        return [
            'email' => $email,
            'initials' => $this->faker->name(),
            'password' => $this->faker->password(),
        ];
    }

    /**
     * @param array<string,string> $input
     */
    private function setValidationExpectations(array $input): void
    {
        $transformedInput = new CreateUserMutationInput(
            $input['email'],
            $input['initials'],
            $input['password']
        );

        $this->transformer->expects($this->once())
            ->method('transform')
            ->with($input)
            ->willReturn($transformedInput);

        $this->validator->expects($this->once())
            ->method('validate')
            ->with($transformedInput);
    }

    /**
     * @param array{email:string,initials:string,password:string} $input
     */
    private function createUserFromInput(array $input): User
    {
        return $this->createUser(
            $input['email'],
            $input['initials'],
            $input['password']
        );
    }

    private function createUser(
        string $email,
        string $initials,
        string $password
    ): User {
        return $this->userFactory->create(
            $email,
            $initials,
            $password,
            $this->uuidTransformer->transformFromString($this->faker->uuid())
        );
    }
}
