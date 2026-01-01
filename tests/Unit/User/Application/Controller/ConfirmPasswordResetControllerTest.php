<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Controller;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\ConfirmPasswordResetCommand;
use App\User\Application\Command\ConfirmPasswordResetCommandResponse;
use App\User\Application\Controller\ConfirmPasswordResetController;
use App\User\Application\DTO\ConfirmPasswordResetDto;
use Symfony\Component\HttpFoundation\JsonResponse;

final class ConfirmPasswordResetControllerTest extends UnitTestCase
{
    private CommandBusInterface $commandBus;
    private ConfirmPasswordResetController $controller;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->controller = new ConfirmPasswordResetController($this->commandBus);
    }

    public function testInvokeDispatchesCommandAndReturnsResponse(): void
    {
        $token = $this->faker->lexify('??????????');
        $newPassword = $this->faker->password(8, 20);

        $dto = new ConfirmPasswordResetDto($token, $newPassword);

        $commandResponse = new ConfirmPasswordResetCommandResponse();
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (ConfirmPasswordResetCommand $command) use ($token, $newPassword, $commandResponse) {
                $this->assertEquals($token, $command->token);
                $this->assertEquals($newPassword, $command->newPassword);

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
        $controller = new ConfirmPasswordResetController($commandBus);

        $this->assertInstanceOf(ConfirmPasswordResetController::class, $controller);
    }
}
