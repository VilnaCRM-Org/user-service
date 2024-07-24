<?php

declare(strict_types=1);

namespace App\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\DTO\RequestPasswordResetDto;
use App\User\Application\Factory\RequestPasswordResetCommandFactoryInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * @implements ProcessorInterface<RequestPasswordResetDto, Response>
 */
final class RequestPasswordResetProcessor implements ProcessorInterface
{
    public function __construct(
        private RequestPasswordResetCommandFactoryInterface $commandFactory,
        private CommandBusInterface $commandBus
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
    ): Response {
        $command = $this->commandFactory->create($data->email);

        $this->commandBus->dispatch($command);

        return new Response();
    }
}
