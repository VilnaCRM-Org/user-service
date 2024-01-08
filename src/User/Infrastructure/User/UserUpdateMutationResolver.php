<?php

declare(strict_types=1);

namespace App\User\Infrastructure\User;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use App\Shared\Domain\Bus\Command\CommandBus;
use App\User\Application\Command\UpdateUserCommand;
use App\User\Application\MutationInput\MutationInputValidator;
use App\User\Application\MutationInput\UpdateUserMutationInput;

class UserUpdateMutationResolver implements MutationResolverInterface
{
    public function __construct(
        private CommandBus $commandBus,
        private MutationInputValidator $validator,
    ) {
    }

    public function __invoke(?object $item, array $context): object
    {
        $args = $context['args']['input'];

        $this->validator->validate($args, new UpdateUserMutationInput($args));

        $user = $item;

        $newEmail = $args['email'] ?? $user->getEmail();
        $newInitials = $args['initials'] ?? $user->getInitials();
        $newPassword = $args['newPassword'] ?? $args['password'];

        $this->commandBus->dispatch(
            new UpdateUserCommand($user, $newEmail, $newInitials, $newPassword, $args['password']));

        return $user;
    }
}
