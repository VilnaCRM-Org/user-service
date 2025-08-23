<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Resolver;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\ConfirmPasswordResetCommand;
use App\User\Application\MutationInput\ConfirmPasswordResetMutationInput;
use App\User\Application\MutationInput\MutationInputValidator;
use App\User\Application\Query\GetUserQueryHandler;
use App\User\Application\Resolver\ConfirmPasswordResetMutationResolver;
use App\User\Application\Transformer\ConfirmPasswordResetMutationInputTransformer;
use App\User\Domain\Entity\PasswordResetToken;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\TokenNotFoundException;
use App\User\Domain\Repository\PasswordResetTokenRepositoryInterface;

final class ConfirmPasswordResetMutationResolverTest extends UnitTestCase
{
    private ConfirmPasswordResetMutationResolver $resolver;
    private PasswordResetTokenRepositoryInterface $passwordResetTokenRepository;
    private CommandBusInterface $commandBus;
    private GetUserQueryHandler $getUserQueryHandler;
    private MutationInputValidator $validator;
    private ConfirmPasswordResetMutationInputTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->passwordResetTokenRepository = $this->createMock(PasswordResetTokenRepositoryInterface::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->getUserQueryHandler = $this->createMock(GetUserQueryHandler::class);
        $this->validator = $this->createMock(MutationInputValidator::class);
        $this->transformer = $this->createMock(ConfirmPasswordResetMutationInputTransformer::class);
        
        $this->resolver = new ConfirmPasswordResetMutationResolver(
            $this->passwordResetTokenRepository,
            $this->commandBus,
            $this->getUserQueryHandler,
            $this->validator,
            $this->transformer
        );
    }

    public function testInvokeWithValidToken(): void
    {
        $tokenValue = $this->faker->sha256();
        $newPassword = $this->faker->password();
        $userID = $this->faker->uuid();
        
        $args = ['token' => $tokenValue, 'newPassword' => $newPassword];
        $context = ['args' => ['input' => $args]];
        
        $mutationInput = new ConfirmPasswordResetMutationInput($tokenValue, $newPassword);
        $token = new PasswordResetToken($tokenValue, $userID);
        $user = $this->createMock(User::class);
        
        $this->transformer->expects($this->once())
            ->method('transform')
            ->with($args)
            ->willReturn($mutationInput);
            
        $this->validator->expects($this->once())
            ->method('validate')
            ->with($mutationInput);
            
        $this->passwordResetTokenRepository->expects($this->once())
            ->method('find')
            ->with($tokenValue)
            ->willReturn($token);
            
        $this->getUserQueryHandler->expects($this->once())
            ->method('handle')
            ->with($userID)
            ->willReturn($user);
            
        $expectedCommand = new ConfirmPasswordResetCommand($token, $newPassword);
        
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->equalTo($expectedCommand));

        $result = $this->resolver->__invoke(null, $context);

        $this->assertSame($user, $result);
    }

    public function testInvokeWithNonExistentTokenThrowsException(): void
    {
        $tokenValue = $this->faker->sha256();
        $newPassword = $this->faker->password();
        
        $args = ['token' => $tokenValue, 'newPassword' => $newPassword];
        $context = ['args' => ['input' => $args]];
        
        $mutationInput = new ConfirmPasswordResetMutationInput($tokenValue, $newPassword);
        
        $this->transformer->expects($this->once())
            ->method('transform')
            ->willReturn($mutationInput);
            
        $this->validator->expects($this->once())
            ->method('validate');
            
        $this->passwordResetTokenRepository->expects($this->once())
            ->method('find')
            ->with($tokenValue)
            ->willReturn(null);
            
        $this->getUserQueryHandler->expects($this->never())
            ->method('handle');
            
        $this->commandBus->expects($this->never())
            ->method('dispatch');

        $this->expectException(TokenNotFoundException::class);

        $this->resolver->__invoke(null, $context);
    }

    public function testInvokeCallsTransformerWithCorrectArgs(): void
    {
        $tokenValue = $this->faker->sha256();
        $newPassword = $this->faker->password();
        $args = ['token' => $tokenValue, 'newPassword' => $newPassword];
        $context = ['args' => ['input' => $args]];
        
        $mutationInput = new ConfirmPasswordResetMutationInput($tokenValue, $newPassword);
        
        $this->transformer->expects($this->once())
            ->method('transform')
            ->with($this->identicalTo($args))
            ->willReturn($mutationInput);
            
        $this->passwordResetTokenRepository->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $this->expectException(TokenNotFoundException::class);
        
        $this->resolver->__invoke(null, $context);
    }

    public function testInvokeCallsValidatorWithTransformedInput(): void
    {
        $tokenValue = $this->faker->sha256();
        $newPassword = $this->faker->password();
        $args = ['token' => $tokenValue, 'newPassword' => $newPassword];
        $context = ['args' => ['input' => $args]];
        
        $mutationInput = new ConfirmPasswordResetMutationInput($tokenValue, $newPassword);
        
        $this->transformer->expects($this->once())
            ->method('transform')
            ->willReturn($mutationInput);
            
        $this->validator->expects($this->once())
            ->method('validate')
            ->with($this->identicalTo($mutationInput));
            
        $this->passwordResetTokenRepository->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $this->expectException(TokenNotFoundException::class);
        
        $this->resolver->__invoke(null, $context);
    }

    public function testInvokeGetsUserAfterValidatingToken(): void
    {
        $tokenValue = $this->faker->sha256();
        $newPassword = $this->faker->password();
        $userID = $this->faker->uuid();
        
        $args = ['token' => $tokenValue, 'newPassword' => $newPassword];
        $context = ['args' => ['input' => $args]];
        
        $mutationInput = new ConfirmPasswordResetMutationInput($tokenValue, $newPassword);
        $token = new PasswordResetToken($tokenValue, $userID);
        $user = $this->createMock(User::class);
        
        $this->transformer->expects($this->once())
            ->method('transform')
            ->willReturn($mutationInput);
            
        $this->validator->expects($this->once())
            ->method('validate');
            
        $this->passwordResetTokenRepository->expects($this->once())
            ->method('find')
            ->willReturn($token);
            
        $this->getUserQueryHandler->expects($this->once())
            ->method('handle')
            ->with($this->identicalTo($userID))
            ->willReturn($user);
            
        $this->commandBus->expects($this->once())
            ->method('dispatch');

        $result = $this->resolver->__invoke(null, $context);

        $this->assertSame($user, $result);
    }

    public function testInvokeDispatchesCommandWithCorrectParameters(): void
    {
        $tokenValue = $this->faker->sha256();
        $newPassword = $this->faker->password();
        $userID = $this->faker->uuid();
        
        $args = ['token' => $tokenValue, 'newPassword' => $newPassword];
        $context = ['args' => ['input' => $args]];
        
        $mutationInput = new ConfirmPasswordResetMutationInput($tokenValue, $newPassword);
        $token = new PasswordResetToken($tokenValue, $userID);
        $user = $this->createMock(User::class);
        
        $this->transformer->expects($this->once())
            ->method('transform')
            ->willReturn($mutationInput);
            
        $this->validator->expects($this->once())
            ->method('validate');
            
        $this->passwordResetTokenRepository->expects($this->once())
            ->method('find')
            ->willReturn($token);
            
        $this->getUserQueryHandler->expects($this->once())
            ->method('handle')
            ->willReturn($user);
            
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($command) use ($token, $newPassword) {
                return $command instanceof ConfirmPasswordResetCommand
                    && $command->token === $token
                    && $command->newPassword === $newPassword;
            }));

        $this->resolver->__invoke(null, $context);
    }
}