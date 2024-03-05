<?php

declare(strict_types=1);

namespace App\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\DTO\ConfirmUserDto;
use App\User\Application\Exception\TokenNotFoundException;
use App\User\Application\Factory\ConfirmUserCommandFactoryInterface;
use App\User\Domain\Repository\TokenRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * @implements ProcessorInterface<ConfirmUserDto, Response>
 */
final readonly class ConfirmUserProcessor implements ProcessorInterface
{
    public function __construct(
        private TokenRepositoryInterface $tokenRepository,
        private CommandBusInterface $commandBus,
        private ConfirmUserCommandFactoryInterface $confirmUserCommandFactory
    ) {
    }

    /**
     * @param ConfirmUserDto $data
     * @param array<string,string> $context
     * @param array<string,string> $uriVariables
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

        $this->commandBus->dispatch(
            $this->confirmUserCommandFactory->create($token)
        );

        return new Response();
    }
}
