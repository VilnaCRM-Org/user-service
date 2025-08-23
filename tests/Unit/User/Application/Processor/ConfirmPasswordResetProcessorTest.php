<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\ConfirmPasswordResetCommand;
use App\User\Application\DTO\ConfirmPasswordResetDto;
use App\User\Application\Processor\ConfirmPasswordResetProcessor;
use App\User\Domain\Entity\PasswordResetToken;
use App\User\Domain\Exception\TokenNotFoundException;
use App\User\Domain\Repository\PasswordResetTokenRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;

final class ConfirmPasswordResetProcessorTest extends UnitTestCase
{
    private ConfirmPasswordResetProcessor $processor;
    private PasswordResetTokenRepositoryInterface $passwordResetTokenRepository;
    private CommandBusInterface $commandBus;
    private Operation $operation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->passwordResetTokenRepository = $this->createMock(PasswordResetTokenRepositoryInterface::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->operation = $this->createMock(Operation::class);
        
        $this->processor = new ConfirmPasswordResetProcessor(
            $this->passwordResetTokenRepository,
            $this->commandBus
        );
    }

    public function testProcessWithValidToken(): void
    {
        $tokenValue = $this->faker->sha256();
        $newPassword = $this->faker->password();
        $userID = $this->faker->uuid();
        
        $dto = new ConfirmPasswordResetDto($tokenValue, $newPassword);
        $token = new PasswordResetToken($tokenValue, $userID);
        
        $this->passwordResetTokenRepository->expects($this->once())
            ->method('find')
            ->with($tokenValue)
            ->willReturn($token);
            
        $expectedCommand = new ConfirmPasswordResetCommand($token, $newPassword);
        
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->equalTo($expectedCommand));

        $result = $this->processor->process($dto, $this->operation);

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('', $result->getContent());
    }

    public function testProcessWithNonExistentTokenThrowsException(): void
    {
        $tokenValue = $this->faker->sha256();
        $newPassword = $this->faker->password();
        
        $dto = new ConfirmPasswordResetDto($tokenValue, $newPassword);
        
        $this->passwordResetTokenRepository->expects($this->once())
            ->method('find')
            ->with($tokenValue)
            ->willReturn(null);
            
        $this->commandBus->expects($this->never())
            ->method('dispatch');

        $this->expectException(TokenNotFoundException::class);

        $this->processor->process($dto, $this->operation);
    }

    public function testProcessWithNullTokenThrowsException(): void
    {
        $dto = new ConfirmPasswordResetDto(null, $this->faker->password());
        
        $this->passwordResetTokenRepository->expects($this->once())
            ->method('find')
            ->with(null)
            ->willReturn(null);
            
        $this->commandBus->expects($this->never())
            ->method('dispatch');

        $this->expectException(TokenNotFoundException::class);

        $this->processor->process($dto, $this->operation);
    }

    public function testProcessWithEmptyTokenThrowsException(): void
    {
        $dto = new ConfirmPasswordResetDto('', $this->faker->password());
        
        $this->passwordResetTokenRepository->expects($this->once())
            ->method('find')
            ->with('')
            ->willReturn(null);
            
        $this->commandBus->expects($this->never())
            ->method('dispatch');

        $this->expectException(TokenNotFoundException::class);

        $this->processor->process($dto, $this->operation);
    }

    public function testProcessWithContextAndUriVariables(): void
    {
        $tokenValue = $this->faker->sha256();
        $newPassword = $this->faker->password();
        $userID = $this->faker->uuid();
        
        $dto = new ConfirmPasswordResetDto($tokenValue, $newPassword);
        $token = new PasswordResetToken($tokenValue, $userID);
        $context = ['context_key' => 'context_value'];
        $uriVariables = ['uri_key' => 'uri_value'];
        
        $this->passwordResetTokenRepository->expects($this->once())
            ->method('find')
            ->with($tokenValue)
            ->willReturn($token);
            
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(ConfirmPasswordResetCommand::class));

        $result = $this->processor->process(
            $dto, 
            $this->operation, 
            $uriVariables, 
            $context
        );

        $this->assertInstanceOf(Response::class, $result);
    }

    public function testRepositoryCallsCorrectMethod(): void
    {
        $tokenValue = $this->faker->sha256();
        $dto = new ConfirmPasswordResetDto($tokenValue, $this->faker->password());
        
        $this->passwordResetTokenRepository->expects($this->once())
            ->method('find')
            ->with($this->identicalTo($tokenValue));

        $this->expectException(TokenNotFoundException::class);
        
        $this->processor->process($dto, $this->operation);
    }
}