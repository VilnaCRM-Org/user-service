<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Builders\ConfirmationTokenBuilder;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\ConfirmPasswordResetCommand;
use App\User\Application\DTO\ConfirmPasswordResetDto;
use App\User\Application\Factory\ConfirmPasswordResetCommandFactoryInterface;
use App\User\Application\Processor\ConfirmPasswordResetProcessor;
use App\User\Domain\Exception\TokenNotFoundException;
use App\User\Domain\Repository\TokenRepositoryInterface;

final class ConfirmPasswordResetProcessorTest extends UnitTestCase
{
    private CommandBusInterface $commandBusMock;
    private TokenRepositoryInterface $tokenRepositoryMock;
    private ConfirmPasswordResetCommandFactoryInterface $confirmPasswordResetCommandFactoryMock;
    private ConfirmPasswordResetCommand $confirmPasswordResetCommandStub;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBusMock = $this->createMock(CommandBusInterface::class);
        $this->tokenRepositoryMock = $this->createMock(TokenRepositoryInterface::class);
        $this->confirmPasswordResetCommandFactoryMock =
            $this->createMock(ConfirmPasswordResetCommandFactoryInterface::class);
        $this->confirmPasswordResetCommandStub = $this->createStub(ConfirmPasswordResetCommand::class);
    }

    public function testProcess(): void
    {
        $token = (new ConfirmationTokenBuilder())->build();
        $newPassword = $this->faker->password();

        $this->tokenRepositoryMock->expects($this->once())->method('find')
            ->with($token->getTokenValue())
            ->willReturn($token);
        $this->confirmPasswordResetCommandFactoryMock->expects($this->once())->method('create')
            ->with($token, $newPassword)
            ->willReturn($this->confirmPasswordResetCommandStub);
        $this->commandBusMock->expects($this->once())->method('dispatch')
            ->with($this->confirmPasswordResetCommandStub);

        $processor = new ConfirmPasswordResetProcessor(
            $this->commandBusMock,
            $this->tokenRepositoryMock,
            $this->confirmPasswordResetCommandFactoryMock
        );

        $processor->process(
            new ConfirmPasswordResetDto($token->getTokenValue(), $newPassword),
            $this->createStub(Operation::class)
        );
    }

    public function testCanHandleNotExistingToken(): void
    {
        $token = (new ConfirmationTokenBuilder())->build();
        $newPassword = $this->faker->password();

        $this->expectException(TokenNotFoundException::class);

        $this->tokenRepositoryMock->expects($this->once())->method('find')
            ->with($token->getTokenValue())
            ->willReturn(null);
        $this->confirmPasswordResetCommandFactoryMock->expects($this->never())->method('create')
            ->with($token, $newPassword)
            ->willReturn($this->confirmPasswordResetCommandStub);
        $this->commandBusMock->expects($this->never())->method('dispatch')
            ->with($this->confirmPasswordResetCommandStub);

        $processor = new ConfirmPasswordResetProcessor(
            $this->commandBusMock,
            $this->tokenRepositoryMock,
            $this->confirmPasswordResetCommandFactoryMock
        );

        $processor->process(
            new ConfirmPasswordResetDto($token->getTokenValue(), $newPassword),
            $this->createStub(Operation::class)
        );
    }
}
