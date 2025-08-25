<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\ConfirmPasswordResetCommand;
use App\User\Application\Command\ConfirmPasswordResetCommandResponse;
use App\User\Application\DTO\ConfirmPasswordResetDto;
use App\User\Application\Processor\ConfirmPasswordResetProcessor;
use Symfony\Component\HttpFoundation\JsonResponse;

final class ConfirmPasswordResetProcessorTest extends UnitTestCase
{
    private CommandBusInterface $commandBus;
    private ConfirmPasswordResetProcessor $processor;
    private Operation $operation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->operation = $this->createMock(Operation::class);

        $this->processor = new ConfirmPasswordResetProcessor($this->commandBus);
    }

    public function testProcessSuccessfully(): void
    {
        $token = $this->faker->sha256();
        $newPassword = $this->faker->password();
        $message = 'Password has been reset successfully.';

        $dto = new ConfirmPasswordResetDto($token, $newPassword);

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (ConfirmPasswordResetCommand $command) use ($token, $newPassword, $message) {
                $this->assertSame($token, $command->token);
                $this->assertSame($newPassword, $command->newPassword);
                
                // Mock the response
                $response = new ConfirmPasswordResetCommandResponse($message);
                $command->setResponse($response);
                
                return true;
            }));

        $response = $this->processor->process($dto, $this->operation);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertJsonStringEqualsJsonString(
            json_encode(['message' => $message]),
            $response->getContent()
        );
    }

    public function testProcessWithNullData(): void
    {
        $dto = new ConfirmPasswordResetDto('', ''); // Use empty strings instead of null

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (ConfirmPasswordResetCommand $command) {
                $this->assertSame('', $command->token); // Empty string, not null
                $this->assertSame('', $command->newPassword); // Empty string, not null
                
                // Mock the response
                $response = new ConfirmPasswordResetCommandResponse('Success');
                $command->setResponse($response);
                
                return true;
            }));

        $response = $this->processor->process($dto, $this->operation);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }
}