<?php

declare(strict_types=1);

namespace App\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Command\ConfirmPasswordResetCommand;
use App\User\Application\DTO\ConfirmPasswordResetDto;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @implements ProcessorInterface<ConfirmPasswordResetDto, JsonResponse>
 */
final readonly class ConfirmPasswordResetProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
    }

    /**
     * @param ConfirmPasswordResetDto $data
     * @param array<string,string> $context
     * @param array<string,string> $uriVariables
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): JsonResponse {
        $command = new ConfirmPasswordResetCommand(
            $data->token,
            $data->newPassword
        );
        $this->commandBus->dispatch($command);

        return new JsonResponse([
            'message' => $command->getResponse()->message,
        ]);
    }
}
