<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\RequestPasswordResetDto;
use App\User\Application\Factory\RequestPasswordResetCommandFactory;
use App\User\Application\Factory\RequestPasswordResetCommandFactoryInterface;
use App\User\Application\Processor\RequestPasswordResetProcessor;
use Symfony\Component\HttpFoundation\Response;

final class RequestPasswordResetProcessorTest extends UnitTestCase
{
    private RequestPasswordResetCommandFactoryInterface $requestPasswordResetCommandFactory;
    private RequestPasswordResetCommandFactoryInterface $mockRequestPasswordResetCommandFactory;
    private Operation $mockOperation;
    private CommandBusInterface $commandBus;
    private RequestPasswordResetProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requestPasswordResetCommandFactory = new RequestPasswordResetCommandFactory();
        $this->mockRequestPasswordResetCommandFactory =
            $this->createMock(RequestPasswordResetCommandFactoryInterface::class);
        $this->mockOperation = $this->createMock(Operation::class);

        $this->commandBus = $this->createMock(CommandBusInterface::class);

        $this->processor = new RequestPasswordResetProcessor(
            $this->mockRequestPasswordResetCommandFactory,
            $this->commandBus,
        );
    }

    public function testProcess(): void
    {
        $email = $this->faker->email();
        $confirmUserDto = new RequestPasswordResetDto($email);

        $resetPasswordRequestedCommand = $this->requestPasswordResetCommandFactory->create($email);
        $this->mockRequestPasswordResetCommandFactory->expects($this->once())
            ->method('create')
            ->with($this->equalTo($email))
            ->willReturn($resetPasswordRequestedCommand);

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->equalTo($resetPasswordRequestedCommand));

        $response = $this->processor->process(
            $confirmUserDto,
            $this->mockOperation
        );

        $this->assertInstanceOf(Response::class, $response);
    }
}
