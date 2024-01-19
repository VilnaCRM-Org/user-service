<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Resolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Command\UpdateUserCommand;
use App\User\Application\MutationInput\MutationInputValidator;
use App\User\Application\Transformer\UpdateUserMutationInputTransformer;

final class UserUpdateMutationResolver implements MutationResolverInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private MutationInputValidator $validator,
        private UpdateUserMutationInputTransformer $transformer
    ) {
    }

    /**
     * @param array<string,string> $context
     */
    public function __invoke(?object $item, array $context): object
    {
        $args = $context['args']['input'];

        $this->validator->validate($this->transformer->transform($args));

        $user = $item;

        $newEmail = $args['email'] ?? $user->getEmail();
        $newInitials = $args['initials'] ?? $user->getInitials();
        $newPassword = $args['newPassword'] ?? $args['password'];

        $this->commandBus->dispatch(
            new UpdateUserCommand(
                $user,
                $newEmail,
                $newInitials,
                $newPassword,
                $args['password']
            )
        );

        return $user;
    }
}
