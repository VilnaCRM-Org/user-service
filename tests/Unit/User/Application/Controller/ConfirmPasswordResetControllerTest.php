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

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->controller = new ConfirmPasswordResetController($this->commandBus);
    }

    public function testInvokeDispatchesCommandAndReturnsResponse(): void
    {
        $userId = $this->faker->uuid();
        $token = $this->faker->lexify('??????????');
        $newPassword = $this->faker->password(8, 20);
        $responseMessage = 'Password reset confirmed successfully';

        $dto = new ConfirmPasswordResetDto($token, $newPassword);

        // Mock command response
        $commandResponse = new ConfirmPasswordResetCommandResponse($responseMessage);

        // Mock command behavior
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (ConfirmPasswordResetCommand $command) use ($token, $newPassword, $commandResponse) {
                // Verify command properties
                $this->assertEquals($token, $command->token);
                $this->assertEquals($newPassword, $command->newPassword);

                // Set response on command
                $command->setResponse($commandResponse);

                return true;
            }));

        $response = ($this->controller)($userId, $dto);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertEquals(['message' => $responseMessage], $content);
    }

    public function testConstructorSetsCommandBus(): void
    {
        $commandBus = $this->createMock(CommandBusInterface::class);
        $controller = new ConfirmPasswordResetController($commandBus);

        $this->assertInstanceOf(ConfirmPasswordResetController::class, $controller);
    }
}
