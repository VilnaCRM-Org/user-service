<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\RequestPasswordResetCommand;
use App\User\Application\DTO\RequestPasswordResetDto;
use App\User\Application\Processor\RequestPasswordResetProcessor;
use Symfony\Component\HttpFoundation\Response;

final class RequestPasswordResetProcessorTest extends UnitTestCase
{
    private RequestPasswordResetProcessor $processor;
    private CommandBusInterface $commandBus;
    private Operation $operation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->operation = $this->createMock(Operation::class);
        
        $this->processor = new RequestPasswordResetProcessor($this->commandBus);
    }

    public function testProcess(): void
    {
        $email = $this->faker->email();
        $dto = new RequestPasswordResetDto($email);
        
        $expectedCommand = new RequestPasswordResetCommand($email);
        
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->equalTo($expectedCommand));

        $result = $this->processor->process($dto, $this->operation);

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('', $result->getContent());
    }

    public function testProcessWithNullEmail(): void
    {
        $dto = new RequestPasswordResetDto(null);
        
        $expectedCommand = new RequestPasswordResetCommand(null);
        
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->equalTo($expectedCommand));

        $result = $this->processor->process($dto, $this->operation);

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testProcessWithEmptyEmail(): void
    {
        $dto = new RequestPasswordResetDto('');
        
        $expectedCommand = new RequestPasswordResetCommand('');
        
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->equalTo($expectedCommand));

        $result = $this->processor->process($dto, $this->operation);

        $this->assertInstanceOf(Response::class, $result);
    }

    public function testProcessWithContextAndUriVariables(): void
    {
        $email = $this->faker->email();
        $dto = new RequestPasswordResetDto($email);
        $context = ['context_key' => 'context_value'];
        $uriVariables = ['uri_key' => 'uri_value'];
        
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(RequestPasswordResetCommand::class));

        $result = $this->processor->process(
            $dto, 
            $this->operation, 
            $uriVariables, 
            $context
        );

        $this->assertInstanceOf(Response::class, $result);
    }

    public function testResponseStatusCode(): void
    {
        $dto = new RequestPasswordResetDto($this->faker->email());
        
        $this->commandBus->expects($this->once())
            ->method('dispatch');

        $result = $this->processor->process($dto, $this->operation);

        $this->assertEquals(200, $result->getStatusCode());
    }
}