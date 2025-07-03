<?php

declare(strict_types=1);

namespace App\User\Application\Resolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Factory\ConfirmUserCommandFactoryInterface;
use App\User\Application\MutationInput\MutationInputValidator;
use App\User\Application\Transformer\ConfirmUserMutationInputTransformer;
use App\User\Domain\Exception\TokenNotFoundException;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\TokenRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Application\Query\GetUserQueryHandler;

final readonly class ConfirmUserMutationResolver implements
    MutationResolverInterface
{
    public function __construct(
        private TokenRepositoryInterface $tokenRepository,
        private CommandBusInterface $commandBus,
        private GetUserQueryHandler $getUserQueryHandler,
        private UserRepositoryInterface $userRepository,
        private MutationInputValidator $validator,
        private ConfirmUserMutationInputTransformer $transformer,
        private ConfirmUserCommandFactoryInterface $confirmUserCommandFactory
    ) {
    }

    /**
     * @param array<string,string> $context
     */
    public function __invoke(?object $item, array $context): ?object
    {
        $args = $context['args']['input'];

        $this->validator->validate($this->transformer->transform($args));

        $token = $this->tokenRepository->find($args['token'])
            ?? throw new TokenNotFoundException();
        $user = $this->getUserQueryHandler->handle($token->getUserID());

        $this->commandBus->dispatch(
            $this->confirmUserCommandFactory->create($token)
        );

        return $user;
    }
}
