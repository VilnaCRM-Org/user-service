<?php

namespace App\User\Infrastructure\Token;

use A\B;
use AdvancedJsonRpc\Request;
use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use App\Shared\Domain\Bus\Command\CommandBus;
use App\User\Application\ConfirmEmailCommand;
use App\User\Domain\Entity\Token\ConfirmationToken;
use App\User\Domain\Entity\User\User;
use App\User\Domain\TokenRepository;
use Symfony\Component\HttpFoundation\Response;

class ConfirmEmailMutationResolver implements MutationResolverInterface
{
    public function __construct(private TokenRepository $tokenRepository, private CommandBus $commandBus)
    {
    }
    public function __invoke(?object $item, array $context): ?object
    {
        $token = $this->tokenRepository->find($item->tokenValue);

        $this->commandBus->dispatch(new ConfirmEmailCommand($token));

        return $token;
    }
}