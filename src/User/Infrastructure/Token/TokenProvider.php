<?php

namespace App\User\Infrastructure\Token;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Shared\Domain\Bus\Command\CommandBus;
use App\User\Application\ConfirmEmailCommand;
use App\User\Domain\TokenRepository;

class TokenProvider implements ProviderInterface
{
    public function __construct(private TokenRepository $tokenRepository, private CommandBus $commandBus)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $token = $this->tokenRepository->find($uriVariables['tokenValue']);

        $this->commandBus->dispatch(new ConfirmEmailCommand($token));

        return $token;
    }
}
