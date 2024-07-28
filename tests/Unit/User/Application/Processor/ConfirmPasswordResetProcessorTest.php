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
    private ConfirmPasswordResetCommand $commandStub;

    private ConfirmPasswordResetCommandFactoryInterface $commandFactoryMock;
    private CommandBusInterface $commandBusMock;
    private TokenRepositoryInterface $tokenRepositoryMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBusMock = $this->createMock(CommandBusInterface::class);
        $this->tokenRepositoryMock = $this->createMock(TokenRepositoryInterface::class);
        $this->commandFactoryMock = $this->createMock(ConfirmPasswordResetCommandFactoryInterface::class);
        $this->commandStub = $this->createStub(ConfirmPasswordResetCommand::class);
    }

    public function testProcess(): void
    {
        $confirmationToken = (new ConfirmationTokenBuilder())->build();
        $newPassword = $this->faker->password();

        $this->tokenRepositoryMock->expects($this->once())->method('find')
            ->with($confirmationToken->getTokenValue())
            ->willReturn($confirmationToken);
        $this->commandFactoryMock->expects($this->once())->method('create')
            ->with($confirmationToken, $newPassword)
            ->willReturn($this->commandStub);
        $this->commandBusMock->expects($this->once())->method('dispatch')
            ->with($this->commandStub);

        $processor = new ConfirmPasswordResetProcessor(
            $this->commandBusMock,
            $this->tokenRepositoryMock,
            $this->commandFactoryMock
        );

        $processor->process(
            new ConfirmPasswordResetDto($confirmationToken->getTokenValue(), $newPassword),
            $this->createStub(Operation::class)
        );
    }

    public function testCanHandleNotExistingToken(): void
    {
        $confirmationToken = (new ConfirmationTokenBuilder())->build();
        $newPassword = $this->faker->password();

        $this->expectException(TokenNotFoundException::class);
        $this->tokenRepositoryMock->expects($this->once())->method('find')
            ->with($confirmationToken->getTokenValue())
            ->willReturn(null);
        $this->commandFactoryMock->expects($this->never())->method('create')
            ->with($confirmationToken, $newPassword)
            ->willReturn($this->commandStub);
        $this->commandBusMock->expects($this->never())->method('dispatch')
            ->with($this->commandStub);

        $processor = new ConfirmPasswordResetProcessor(
            $this->commandBusMock,
            $this->tokenRepositoryMock,
            $this->commandFactoryMock
        );

        $processor->process(
            new ConfirmPasswordResetDto($confirmationToken->getTokenValue(), $newPassword),
            $this->createStub(Operation::class)
        );
    }
}
