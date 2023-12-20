<?php

namespace App\User\Infrastructure\Token;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use App\Shared\Domain\Bus\Command\CommandBus;
use App\User\Application\ConfirmUserCommand;
use App\User\Domain\Entity\Token\ConfirmUserDto;
use App\User\Domain\TokenRepository;
use App\User\Domain\UserRepository;

class ConfirmUserMutationResolver implements MutationResolverInterface
{
    public function __construct(private TokenRepository $tokenRepository, private CommandBus $commandBus,
        private UserRepository $userRepository)
    {
    }

    /**
     * @param ConfirmUserDto $item
     */
    public function __invoke(?object $item, array $context): ?object
    {
        $token = $this->tokenRepository->findByTokenValue($item->token);
        $user = $this->userRepository->find($token->getUserID());

        $this->commandBus->dispatch(new ConfirmUserCommand($token));

        return $user;
    }
}
