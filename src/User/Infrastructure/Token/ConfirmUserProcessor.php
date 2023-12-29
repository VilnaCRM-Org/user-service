<?php

namespace App\User\Infrastructure\Token;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Domain\Bus\Command\CommandBus;
use App\User\Application\Command\ConfirmUserCommand;
use App\User\Application\DTO\Token\ConfirmUserDto;
use App\User\Domain\Entity\User;
use App\User\Domain\TokenRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * @implements ProcessorInterface<Response>
 */
class ConfirmUserProcessor implements ProcessorInterface
{
    public function __construct(private TokenRepositoryInterface $tokenRepository, private CommandBus $commandBus)
    {
    }

    /**
     * @param ConfirmUserDto $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        $token = $this->tokenRepository->findByTokenValue($data->token);

        $this->commandBus->dispatch(new ConfirmUserCommand($token));

        return new Response(status: 200);
    }
}
