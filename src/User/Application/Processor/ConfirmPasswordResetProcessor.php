<?php

declare(strict_types=1);

namespace App\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Command\ConfirmPasswordResetCommand;
use App\User\Application\DTO\ConfirmPasswordResetDto;
use App\User\Application\DTO\PasswordResetPayload;

/**
 * @implements ProcessorInterface<ConfirmPasswordResetDto, PasswordResetPayload>
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
    ): PasswordResetPayload {
        $command = new ConfirmPasswordResetCommand(
            $data->token,
            $data->newPassword
        );
        $this->commandBus->dispatch($command);

        return new PasswordResetPayload(true);
    }
}
