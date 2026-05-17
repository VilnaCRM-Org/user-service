<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Resolver;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\MutationInput\CreateUserMutationInput;
use App\User\Application\Registration\RegisterUserOrchestrator;
use App\User\Application\Resolver\RegisterUserMutationResolver;
use App\User\Application\Transformer\CreateUserMutationInputTransformer;
use App\User\Application\Validator\MutationInputValidator;
use App\User\Domain\Entity\UserInterface;
use PHPUnit\Framework\MockObject\MockObject;

final class RegisterUserMutationResolverTest extends UnitTestCase
{
    private MutationInputValidator&MockObject $validator;
    private CreateUserMutationInputTransformer&MockObject $transformer;
    private RegisterUserOrchestrator&MockObject $registerUserOrchestrator;
    private RegisterUserMutationResolver $resolver;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = $this->createMock(MutationInputValidator::class);
        $this->transformer =
            $this->createMock(CreateUserMutationInputTransformer::class);
        $this->registerUserOrchestrator = $this->createMock(RegisterUserOrchestrator::class);
        $this->resolver = new RegisterUserMutationResolver(
            $this->validator,
            $this->transformer,
            $this->registerUserOrchestrator
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

        $this->expectValidation($input, $transformedInput);
        $this->registerUserOrchestrator->expects($this->once())
            ->method('register')
            ->with(
                $transformedInput->email,
                $transformedInput->initials,
                $transformedInput->password
            )
            ->willReturn($user);

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
        $validationFailure = new \RuntimeException('Invalid input.');

        $this->transformer->expects($this->once())
            ->method('transform')
            ->with($input)
            ->willReturn($transformedInput);
        $this->validator->expects($this->once())
            ->method('validate')
            ->with($transformedInput)
            ->willThrowException($validationFailure);
        $this->registerUserOrchestrator->expects($this->never())
            ->method('register');

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

    /**
     * @param array{email?:string,initials:string,password:string} $input
     */
    private function invokeResolver(array $input): ?object
    {
        return $this->resolver->__invoke(null, ['args' => ['input' => $input]]);
    }
}
