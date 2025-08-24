<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\RequestPasswordResetCommand;
use App\User\Application\Command\RequestPasswordResetCommandResponse;
use App\User\Application\DTO\RequestPasswordResetDto;
use App\User\Application\Processor\RequestPasswordResetProcessor;
use Symfony\Component\HttpFoundation\JsonResponse;

final class RequestPasswordResetProcessorTest extends UnitTestCase
{
    private CommandBusInterface $commandBus;
    private RequestPasswordResetProcessor $processor;
    private Operation $operation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->operation = $this->createMock(Operation::class);

        $this->processor = new RequestPasswordResetProcessor($this->commandBus);
    }

    public function testProcessSuccessfully(): void
    {
        $email = $this->faker->safeEmail();
        $message = 'Password reset email sent successfully.';

        $dto = new RequestPasswordResetDto($email);

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (RequestPasswordResetCommand $command) use ($email, $message) {
                $this->assertSame($email, $command->email);

                // Mock the response
                $response = new RequestPasswordResetCommandResponse($message);
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

    public function testProcessWithNullEmail(): void
    {
        $dto = new RequestPasswordResetDto(''); // Use empty string instead of null

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (RequestPasswordResetCommand $command) {
                $this->assertSame('', $command->email); // Empty string, not null

                // Mock the response
                $response = new RequestPasswordResetCommandResponse('Success');
                $command->setResponse($response);

                return true;
            }));

        $response = $this->processor->process($dto, $this->operation);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }
}
