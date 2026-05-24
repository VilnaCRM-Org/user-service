<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Resolver;

use App\Shared\Application\Bus\Guard\CommandResponseTypeGuard;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\RegisterUserCommand;
use App\User\Application\DTO\RegisterUserCommandResponse;
use App\User\Application\Factory\SignUpCommandFactoryInterface;
use App\User\Application\MutationInput\CreateUserMutationInput;
use App\User\Application\Resolver\RegisterUserMutationResolver;
use App\User\Application\Transformer\CreateUserMutationInputTransformer;
use App\User\Application\Validator\MutationInputValidator;
use App\User\Domain\Entity\UserInterface;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;

final class RegisterUserMutationResolverTest extends UnitTestCase
{
    private MutationInputValidator&MockObject $validator;
    private CreateUserMutationInputTransformer&MockObject $transformer;
    private SignUpCommandFactoryInterface&MockObject $commandFactory;
    private CommandBusInterface&MockObject $commandBus;
    private RegisterUserMutationResolver $resolver;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = $this->createMock(MutationInputValidator::class);
        $this->transformer =
            $this->createMock(CreateUserMutationInputTransformer::class);
        $this->commandFactory =
            $this->createMock(SignUpCommandFactoryInterface::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->resolver = new RegisterUserMutationResolver(
            $this->validator,
            $this->transformer,
            $this->commandFactory,
            $this->commandBus,
            new CommandResponseTypeGuard()
        );
    }

    public function testInvokeValidatesAndDelegatesRegistration(): void
    {
        $input = $this->createInput();
        $transformedInput = new CreateUserMutationInput(
            $this->faker->email(),
            $this->faker->name(),
            $this->faker->password()
        );
        $user = $this->createMock(UserInterface::class);
        $command = new RegisterUserCommand(
            $transformedInput->email,
            $transformedInput->initials,
            $transformedInput->password
        );

        $this->expectValidation($input, $transformedInput);
        $this->expectCommandDispatch($transformedInput, $command, $user);

        $this->assertSame($user, $this->invokeResolver($input));
    }

    public function testInvokeValidatesBeforeReadingRequiredEmail(): void
    {
        $input = $this->createInputWithoutEmail();
        $transformedInput = new CreateUserMutationInput(
            null,
            $input['initials'],
            $input['password']
        );
        $validationFailure = new RuntimeException('Invalid input.');

        $this->expectValidationFailure(
            $input,
            $transformedInput,
            $validationFailure
        );
        $this->expectNoRegistrationDispatch();

        $this->expectExceptionObject($validationFailure);

        $this->invokeResolver($input);
    }

    /**
     * @return array{email:string,initials:string,password:string}
     */
    private function createInput(): array
    {
        return [
            'email' => $this->faker->email(),
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
     * @param array{email:string,initials:string,password:string} $input
     */
    private function expectValidation(
        array $input,
        CreateUserMutationInput $transformedInput,
    ): void {
        $this->transformer->expects($this->once())
            ->method('transform')
            ->with($input)
            ->willReturn($transformedInput);

        $this->validator->expects($this->once())
            ->method('validate')
            ->with($transformedInput);
    }

    private function expectCommandDispatch(
        CreateUserMutationInput $transformedInput,
        RegisterUserCommand $command,
        UserInterface $user,
    ): void {
        $this->commandFactory->expects($this->once())
            ->method('create')
            ->with(
                $transformedInput->email,
                $transformedInput->initials,
                $transformedInput->password
            )
            ->willReturn($command);
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($command)
            ->willReturn(new RegisterUserCommandResponse($user));
    }

    /**
     * @param array{email?:string,initials:string,password:string} $input
     */
    private function expectValidationFailure(
        array $input,
        CreateUserMutationInput $transformedInput,
        RuntimeException $validationFailure,
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

    private function expectNoRegistrationDispatch(): void
    {
        $this->commandFactory->expects($this->never())
            ->method('create');
        $this->commandBus->expects($this->never())
            ->method('dispatch');
    }

    /**
     * @param array{email?:string,initials:string,password:string} $input
     */
    private function invokeResolver(array $input): ?object
    {
        return $this->resolver->__invoke(null, ['args' => ['input' => $input]]);
    }
}
