<?php

declare(strict_types=1);

namespace App\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Command\RequestPasswordResetCommand;
use App\User\Application\DTO\PasswordResetPayload;
use App\User\Application\DTO\RequestPasswordResetDto;

/**
 * @implements ProcessorInterface<RequestPasswordResetDto, PasswordResetPayload>
 */
final readonly class RequestPasswordResetProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
    }

    /**
     * @param RequestPasswordResetDto $data
     * @param array<string,string> $context
     * @param array<string,string> $uriVariables
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): PasswordResetPayload {
        $command = new RequestPasswordResetCommand($data->email);
        $this->commandBus->dispatch($command);

        return new PasswordResetPayload(true);
    }
}