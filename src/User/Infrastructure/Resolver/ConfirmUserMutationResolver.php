<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Resolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Command\ConfirmUserCommand;
use App\User\Application\MutationInput\ConfirmUserMutationInput;
use App\User\Application\MutationInput\MutationInputValidator;
use App\User\Domain\Repository\TokenRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Exception\TokenNotFoundException;
use App\User\Infrastructure\Exception\UserNotFoundException;

class ConfirmUserMutationResolver implements MutationResolverInterface
{
    public function __construct(
        private TokenRepositoryInterface $tokenRepository,
        private CommandBusInterface $commandBus,
        private UserRepositoryInterface $userRepository,
        private MutationInputValidator $validator
    ) {
    }

    public function __invoke(?object $item, array $context): ?object
    {
        $args = $context['args']['input'];

        $this->validator->validate($args, new ConfirmUserMutationInput());

        $token = $this->tokenRepository->find($args['token']) ?? throw new TokenNotFoundException();
        $user = $this->userRepository->find($token->getUserID()) ?? throw new UserNotFoundException();

        $this->commandBus->dispatch(new ConfirmUserCommand($token));

        return $user;
    }
}
