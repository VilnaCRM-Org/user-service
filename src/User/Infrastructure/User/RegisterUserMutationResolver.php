<?php

declare(strict_types=1);

namespace App\User\Infrastructure\User;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use App\Shared\Domain\Bus\Command\CommandBus;
use App\User\Application\Command\SignUpCommand;
use App\User\Infrastructure\MutationInputValidator;

class RegisterUserMutationResolver implements MutationResolverInterface
{
    public function __construct(
        private CommandBus $commandBus,
        private MutationInputValidator $validator
    ) {
    }

    public function __invoke(?object $item, array $context): ?object
    {
        $this->validator->validate($item);

        $command = new SignUpCommand($item->email, $item->initials, $item->password);
        $this->commandBus->dispatch($command);

        return $command->getResponse()->getCreatedUser();
    }
}
