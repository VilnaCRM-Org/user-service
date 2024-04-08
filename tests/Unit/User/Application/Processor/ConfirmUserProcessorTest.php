<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\ConfirmUserDto;
use App\User\Application\Factory\ConfirmUserCommandFactory;
use App\User\Application\Factory\ConfirmUserCommandFactoryInterface;
use App\User\Application\Processor\ConfirmUserProcessor;
use App\User\Domain\Exception\TokenNotFoundException;
use App\User\Domain\Factory\ConfirmationTokenFactory;
use App\User\Domain\Factory\ConfirmationTokenFactoryInterface;
use App\User\Domain\Repository\TokenRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;

final class ConfirmUserProcessorTest extends UnitTestCase
{
    private ConfirmationTokenFactoryInterface $confirmationTokenFactory;
    private ConfirmUserCommandFactoryInterface $confirmUserCommandFactory;
    private Operation $mockOperation;
    private TokenRepositoryInterface $tokenRepository;
    private CommandBusInterface $commandBus;
    private ConfirmUserCommandFactoryInterface $mockConfirmUserCommandFactory;
    private ConfirmUserProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->confirmationTokenFactory = new ConfirmationTokenFactory(
            $this->faker->numberBetween(1, 10)
        );
        $this->confirmUserCommandFactory = new ConfirmUserCommandFactory();
        $this->mockOperation =
            $this->createMock(Operation::class);
        $this->tokenRepository = $this->createMock(TokenRepositoryInterface::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->mockConfirmUserCommandFactory = $this->createMock(
            ConfirmUserCommandFactoryInterface::class
        );
        $this->processor = new ConfirmUserProcessor(
            $this->tokenRepository,
            $this->commandBus,
            $this->mockConfirmUserCommandFactory
        );
    }

    public function testProcess(): void
    {
        $confirmUserDto = new ConfirmUserDto($this->faker->uuid());

        $token = $this->confirmationTokenFactory->create($this->faker->uuid());
        $this->tokenRepository->expects($this->once())
            ->method('find')
            ->with($this->equalTo($confirmUserDto->token))
            ->willReturn($token);

        $confirmUserCommand = $this->confirmUserCommandFactory->create($token);
        $this->mockConfirmUserCommandFactory->expects($this->once())
            ->method('create')
            ->with($this->equalTo($token))
            ->willReturn($confirmUserCommand);

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->equalTo($confirmUserCommand));

        $response = $this->processor->process($confirmUserDto, $this->mockOperation);

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testProcessTokenNotFoundException(): void
    {
        $confirmUserDto = new ConfirmUserDto($this->faker->uuid());

        $this->tokenRepository->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $this->expectException(TokenNotFoundException::class);

        $this->processor->process($confirmUserDto, $this->mockOperation);
    }
}
