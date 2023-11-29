<?php

namespace App\User\Infrastructure\Token;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Domain\Bus\Command\CommandBus;
use App\User\Application\ConfirmEmailCommand;
use App\User\Domain\Entity\Token\ConfirmEmailInputDto;
use App\User\Domain\TokenRepository;
use Symfony\Component\HttpFoundation\Response;

class TokenProcessor implements ProcessorInterface
{
    public function __construct(private TokenRepository $tokenRepository, private CommandBus $commandBus)
    {
    }

    /**
     * @param ConfirmEmailInputDto $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        $token = $this->tokenRepository->find($data->tokenValue);

        $this->commandBus->dispatch(new ConfirmEmailCommand($token));

        return new Response(status: 200);
    }
}
