<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Controller;

use App\Shared\Application\Bus\Guard\CommandResponseTypeGuard;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\ConfirmPasswordResetCommand;
use App\User\Application\Controller\ConfirmPasswordResetController;
use App\User\Application\DTO\ConfirmPasswordResetCommandResponse;
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
        $this->controller = new ConfirmPasswordResetController(
            $this->commandBus,
            new CommandResponseTypeGuard()
        );
    }

    public function testInvokeDispatchesCommandAndReturnsResponse(): void
    {
        $testData = $this->createTestData();
        $dto = new ConfirmPasswordResetDto($testData['token'], $testData['newPassword']);

        $this->setupCommandBusExpectations($testData);

        $response = ($this->controller)($dto);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(204, $response->getStatusCode());
    }

    public function testConstructorSetsCommandBus(): void
    {
        $commandBus = $this->createMock(CommandBusInterface::class);
        $controller = new ConfirmPasswordResetController(
            $commandBus,
            new CommandResponseTypeGuard()
        );

        $this->assertInstanceOf(ConfirmPasswordResetController::class, $controller);
    }

    /**
     * @return array<string>
     *
     * @psalm-return array{token: string, newPassword: string}
     */
    private function createTestData(): array
    {
        return [
            'token' => $this->faker->lexify('??????????'),
            'newPassword' => $this->faker->password(8, 20),
        ];
    }

    /**
     * @param array<string, string> $testData
     */
    private function setupCommandBusExpectations(array $testData): void
    {
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(
                fn (ConfirmPasswordResetCommand $cmd) => $this->validateCommand(
                    $cmd,
                    $testData['token'],
                    $testData['newPassword']
                )
            ))
            ->willReturn(new ConfirmPasswordResetCommandResponse());
    }

    private function validateCommand(
        ConfirmPasswordResetCommand $command,
        string $token,
        string $newPassword
    ): bool {
        $this->assertEquals($token, $command->token);
        $this->assertEquals($newPassword, $command->newPassword);

        return true;
    }
}
