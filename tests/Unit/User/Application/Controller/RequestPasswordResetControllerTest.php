<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Controller;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\RequestPasswordResetCommand;
use App\User\Application\Command\RequestPasswordResetCommandResponse;
use App\User\Application\Controller\RequestPasswordResetController;
use App\User\Application\DTO\RequestPasswordResetDto;
use Symfony\Component\HttpFoundation\JsonResponse;

final class RequestPasswordResetControllerTest extends UnitTestCase
{
    private CommandBusInterface $commandBus;
    private RequestPasswordResetController $controller;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->controller = new RequestPasswordResetController($this->commandBus);
    }

    public function testInvokeDispatchesCommandAndReturnsResponse(): void
    {
        $email = $this->faker->email();
        $responseMessage = '';

        $dto = new RequestPasswordResetDto($email);

        $commandResponse = new RequestPasswordResetCommandResponse($responseMessage);

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (RequestPasswordResetCommand $command) use ($email, $commandResponse) {
                $this->assertEquals($email, $command->email);

                $command->setResponse($commandResponse);

                return true;
            }));

        $response = ($this->controller)($dto);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(204, $response->getStatusCode());
    }

    public function testConstructorSetsCommandBus(): void
    {
        $commandBus = $this->createMock(CommandBusInterface::class);
        $controller = new RequestPasswordResetController($commandBus);

        $this->assertInstanceOf(RequestPasswordResetController::class, $controller);
    }
}
