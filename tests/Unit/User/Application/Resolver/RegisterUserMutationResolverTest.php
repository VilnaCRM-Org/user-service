<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Resolver;

use App\Tests\Unit\User\Application\Support\RegisterUserCommandTestCase;
use App\User\Application\Command\RegisterUserCommand;
use App\User\Application\MutationInput\CreateUserMutationInput;
use App\User\Application\Resolver\RegisterUserMutationResolver;
use App\User\Application\Transformer\CreateUserMutationInputTransformer;
use App\User\Application\Validator\MutationInputValidator;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\UserNotFoundException;

final class RegisterUserMutationResolverTest extends RegisterUserCommandTestCase
{
    private MutationInputValidator $validator;
    private CreateUserMutationInputTransformer $transformer;
    private RegisterUserMutationResolver $resolver;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpRegisterUserCommandContext();
        $this->createResolverMocks();
        $this->resolver = $this->createResolver();
    }

    public function testInvokeReturnsExistingUserWithoutDispatch(): void
    {
        $email = $this->faker->email();
        $input = $this->createInput($email);
        $user = $this->createUserFromInput($input);

        $this->setValidationExpectations($input);
        $this->expectExistingUserLookup($email, $user);
        $this->expectNoRegistrationAttempt();

        $this->assertSame($user, $this->invokeResolver($input));
    }

    public function testInvokeDispatchesRegistrationAndReturnsCreatedUser(): void
    {
        $email = $this->faker->email();
        $input = $this->createInput($email);
        $user = $this->createUserFromInput($input);

        $this->setValidationExpectations($input);
        $this->expectCreatedUserLookup($email, $input, $user);

        $this->assertSame($user, $this->invokeResolver($input));
    }

    public function testInvokeThrowsWhenCreatedUserCannotBeLoaded(): void
    {
        $email = $this->faker->email();
        $input = $this->createInput($email);
        $command = $this->createRegistrationCommand($email, $input);

        $this->expectException(UserNotFoundException::class);
        $this->setValidationExpectations($input);
        $this->commandExpectationHelper->expectMissingCreatedUser(
            $email,
            $command
        );

        $this->invokeResolver($input);
    }

    public function testInvokeValidatesBeforeReadingRequiredEmail(): void
    {
        $input = $this->createInputWithoutEmail();
        $transformedInput = $this->createTransformedInput($input);
        $validationFailure = new \RuntimeException('Invalid input.');

        $this->expectValidationFailure(
            $input,
            $transformedInput,
            $validationFailure
        );
        $this->expectNoRegistrationAttempt();

        $this->expectExceptionObject($validationFailure);

        $this->invokeResolver($input);
    }

    private function createResolverMocks(): void
    {
        $this->validator = $this->createMock(MutationInputValidator::class);
        $this->transformer =
            $this->createMock(CreateUserMutationInputTransformer::class);
    }

    private function createResolver(): RegisterUserMutationResolver
    {
        return new RegisterUserMutationResolver(
            $this->commandBus,
            $this->validator,
            $this->transformer,
            $this->mockSignUpCommandFactory,
            $this->findUserByEmailQueryHandler
        );
    }

    /**
     * @param array{email:string,initials:string,password:string} $input
     */
    private function expectCreatedUserLookup(
        string $email,
        array $input,
        User $user,
    ): void {
        $this->commandExpectationHelper->expectRegistration(
            $email,
            $input['initials'],
            $input['password'],
            $this->createRegistrationCommand($email, $input),
            $user
        );
    }

    private function expectExistingUserLookup(string $email, User $user): void
    {
        $this->findUserByEmailQueryHandler->expects($this->once())
            ->method('find')
            ->with($email)
            ->willReturn($user);
    }

    /**
     * @param array<string,string> $input
     */
    private function expectValidationFailure(
        array $input,
        CreateUserMutationInput $transformedInput,
        \RuntimeException $validationFailure,
    ): void {
        $this->transformer->expects($this->once())
            ->method('transform')
            ->with($input)
            ->willReturn($transformedInput);
        $this->validator->expects($this->once())
            ->method('validate')
            ->with($transformedInput)
            ->willThrowException($validationFailure);
    }

    private function expectNoRegistrationAttempt(): void
    {
        $this->mockSignUpCommandFactory->expects($this->never())
            ->method('create');
        $this->commandBus->expects($this->never())
            ->method('dispatch');
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
     * @return array{initials:string,password:string}
     */
    private function createInputWithoutEmail(): array
    {
        return [
            'initials' => $this->faker->name(),
            'password' => $this->faker->password(),
        ];
    }

    /**
     * @param array{initials:string,password:string} $input
     */
    private function createTransformedInput(array $input): CreateUserMutationInput
    {
        return new CreateUserMutationInput(
            null,
            $input['initials'],
            $input['password']
        );
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
     * @param array{email?:string,initials:string,password:string} $input
     */
    private function invokeResolver(array $input): ?object
    {
        return $this->resolver->__invoke(null, ['args' => ['input' => $input]]);
    }

    /**
     * @param array{email:string,initials:string,password:string} $input
     */
    private function createRegistrationCommand(
        string $email,
        array $input,
    ): RegisterUserCommand {
        return $this->signUpCommandFactory->create(
            $email,
            $input['initials'],
            $input['password']
        );
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
}
