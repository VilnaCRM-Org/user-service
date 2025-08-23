<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Resolver;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\RequestPasswordResetCommand;
use App\User\Application\MutationInput\MutationInputValidator;
use App\User\Application\MutationInput\RequestPasswordResetMutationInput;
use App\User\Application\Resolver\RequestPasswordResetMutationResolver;
use App\User\Application\Transformer\RequestPasswordResetMutationInputTransformer;

final class RequestPasswordResetMutationResolverTest extends UnitTestCase
{
    private RequestPasswordResetMutationResolver $resolver;
    private CommandBusInterface $commandBus;
    private MutationInputValidator $validator;
    private RequestPasswordResetMutationInputTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->validator = $this->createMock(MutationInputValidator::class);
        $this->transformer = $this->createMock(RequestPasswordResetMutationInputTransformer::class);
        
        $this->resolver = new RequestPasswordResetMutationResolver(
            $this->commandBus,
            $this->validator,
            $this->transformer
        );
    }

    public function testInvokeWithValidInput(): void
    {
        $email = $this->faker->email();
        $args = ['email' => $email];
        $context = ['args' => ['input' => $args]];
        
        $mutationInput = new RequestPasswordResetMutationInput($email);
        $expectedCommand = new RequestPasswordResetCommand($email);
        
        $this->transformer->expects($this->once())
            ->method('transform')
            ->with($args)
            ->willReturn($mutationInput);
            
        $this->validator->expects($this->once())
            ->method('validate')
            ->with($mutationInput);
            
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->equalTo($expectedCommand));

        $result = $this->resolver->__invoke(null, $context);

        $this->assertNull($result);
    }

    public function testInvokeWithNullEmail(): void
    {
        $args = ['email' => null];
        $context = ['args' => ['input' => $args]];
        
        $mutationInput = new RequestPasswordResetMutationInput(null);
        $expectedCommand = new RequestPasswordResetCommand(null);
        
        $this->transformer->expects($this->once())
            ->method('transform')
            ->with($args)
            ->willReturn($mutationInput);
            
        $this->validator->expects($this->once())
            ->method('validate')
            ->with($mutationInput);
            
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->equalTo($expectedCommand));

        $result = $this->resolver->__invoke(null, $context);

        $this->assertNull($result);
    }

    public function testInvokeCallsTransformerWithCorrectArgs(): void
    {
        $email = $this->faker->email();
        $args = ['email' => $email];
        $context = ['args' => ['input' => $args]];
        
        $mutationInput = new RequestPasswordResetMutationInput($email);
        
        $this->transformer->expects($this->once())
            ->method('transform')
            ->with($this->identicalTo($args))
            ->willReturn($mutationInput);
            
        $this->validator->expects($this->once())
            ->method('validate');
            
        $this->commandBus->expects($this->once())
            ->method('dispatch');

        $this->resolver->__invoke(null, $context);
    }

    public function testInvokeCallsValidatorWithTransformedInput(): void
    {
        $email = $this->faker->email();
        $args = ['email' => $email];
        $context = ['args' => ['input' => $args]];
        
        $mutationInput = new RequestPasswordResetMutationInput($email);
        
        $this->transformer->expects($this->once())
            ->method('transform')
            ->willReturn($mutationInput);
            
        $this->validator->expects($this->once())
            ->method('validate')
            ->with($this->identicalTo($mutationInput));
            
        $this->commandBus->expects($this->once())
            ->method('dispatch');

        $this->resolver->__invoke(null, $context);
    }

    public function testInvokeReturnsNullForSecurity(): void
    {
        $args = ['email' => $this->faker->email()];
        $context = ['args' => ['input' => $args]];
        
        $mutationInput = new RequestPasswordResetMutationInput();
        
        $this->transformer->expects($this->once())
            ->method('transform')
            ->willReturn($mutationInput);
            
        $this->validator->expects($this->once())
            ->method('validate');
            
        $this->commandBus->expects($this->once())
            ->method('dispatch');

        $result = $this->resolver->__invoke(null, $context);

        // Returns null for security reasons to not reveal if email exists
        $this->assertNull($result);
    }

    public function testInvokeWithComplexEmail(): void
    {
        $email = 'test+complex.email@sub.domain.co.uk';
        $args = ['email' => $email];
        $context = ['args' => ['input' => $args]];
        
        $mutationInput = new RequestPasswordResetMutationInput($email);
        
        $this->transformer->expects($this->once())
            ->method('transform')
            ->willReturn($mutationInput);
            
        $this->validator->expects($this->once())
            ->method('validate');
            
        $this->commandBus->expects($this->once())
            ->method('dispatch');

        $result = $this->resolver->__invoke(null, $context);

        $this->assertNull($result);
    }
}