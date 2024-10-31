<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\RequestPasswordResetCommand;
use App\User\Application\DTO\RequestPasswordResetDto;
use App\User\Application\Factory\RequestPasswordResetCommandFactoryInterface;
use App\User\Application\Processor\RequestPasswordResetProcessor;

final class RequestPasswordResetProcessorTest extends UnitTestCase
{
    private RequestPasswordResetCommand $commandStub;

    private RequestPasswordResetCommandFactoryInterface $commandFactoryMock;
    private CommandBusInterface $commandBusMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandStub = $this->createStub(RequestPasswordResetCommand::class);

        $this->commandFactoryMock = $this->createMock(RequestPasswordResetCommandFactoryInterface::class);
        $this->commandBusMock = $this->createMock(CommandBusInterface::class);
    }

    public function testProcess(): void
    {
        $email = $this->faker->email();

        $this->commandFactoryMock->expects($this->once())
            ->method('create')
            ->with($email)
            ->willReturn($this->commandStub);

        $this->commandBusMock->expects($this->once())
            ->method('dispatch')
            ->with($this->commandStub);

        $processor = new RequestPasswordResetProcessor(
            $this->commandFactoryMock,
            $this->commandBusMock,
        );
        $processor->process(
            new RequestPasswordResetDto($email),
            $this->createStub(Operation::class)
        );
    }
}
