<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Resolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Command\ConfirmUserCommand;
use App\User\Application\MutationInput\MutationInputValidator;
use App\User\Application\Transformer\ConfirmUserMutationInputTransformer;
use App\User\Domain\Repository\TokenRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Exception\TokenNotFoundException;
use App\User\Infrastructure\Exception\UserNotFoundException;

final class ConfirmUserMutationResolver implements MutationResolverInterface
{
    public function __construct(
        private TokenRepositoryInterface $tokenRepository,
        private CommandBusInterface $commandBus,
        private UserRepositoryInterface $userRepository,
        private MutationInputValidator $validator,
        private ConfirmUserMutationInputTransformer $transformer
    ) {
    }

    /**
     * @param array<string,string> $context
     */
    public function __invoke(?object $item, array $context): ?object
    {
        $args = $context['args']['input'];

        $this->validator->validate($this->transformer->transform($args));

        $token = $this->tokenRepository->find($args['token']) ?? throw new TokenNotFoundException();
        $user = $this->userRepository->find($token->getUserID()) ?? throw new UserNotFoundException();

        $this->commandBus->dispatch(new ConfirmUserCommand($token));

        return $user;
    }
}
