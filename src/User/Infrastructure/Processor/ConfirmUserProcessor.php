<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Command\ConfirmUserCommand;
use App\User\Application\DTO\ConfirmUserDto;
use App\User\Domain\Repository\TokenRepositoryInterface;
use App\User\Infrastructure\Exception\TokenNotFoundException;
use Symfony\Component\HttpFoundation\Response;

/**
 * @implements ProcessorInterface<Response>
 */
final class ConfirmUserProcessor implements ProcessorInterface
{
    public function __construct(
        private TokenRepositoryInterface $tokenRepository,
        private CommandBusInterface $commandBus
    ) {
    }

    /**
     * @param ConfirmUserDto $data
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): Response {
        $token = $this->tokenRepository->find(
            $data->token
        ) ?? throw new TokenNotFoundException();

        $this->commandBus->dispatch(new ConfirmUserCommand($token));

        return new Response();
    }
}
