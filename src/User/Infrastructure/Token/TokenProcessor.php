<?php

namespace App\User\Infrastructure\Token;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Domain\Bus\Command\CommandBus;
use App\User\Application\ConfirmUserCommand;
use App\User\Domain\Entity\Token\ConfirmUserDto;
use App\User\Domain\TokenRepository;
use Symfony\Component\HttpFoundation\Response;

class TokenProcessor implements ProcessorInterface
{
    public function __construct(private TokenRepository $tokenRepository, private CommandBus $commandBus)
    {
    }

    /**
     * @param ConfirmUserDto $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        $token = $this->tokenRepository->find($data->token);

        $this->commandBus->dispatch(new ConfirmUserCommand($token));

        return new Response(status: 200);
    }
}
