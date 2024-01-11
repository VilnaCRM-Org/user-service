<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Resolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Command\SignUpCommand;
use App\User\Application\MutationInput\CreateUserMutationInput;
use App\User\Application\MutationInput\MutationInputValidator;

class RegisterUserMutationResolver implements MutationResolverInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private MutationInputValidator $validator
    ) {
    }

    public function __invoke(?object $item, array $context): ?object
    {
        $args = $context['args']['input'];

        $this->validator->validate($args, new CreateUserMutationInput());

        $command = new SignUpCommand($args['email'], $args['initials'], $args['password']);
        $this->commandBus->dispatch($command);

        return $command->getResponse()->createdUser;
    }
}
