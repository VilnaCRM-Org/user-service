<?php

namespace App\User\Infrastructure\User;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use App\Shared\Domain\Bus\Command\CommandBus;
use App\User\Application\SendConfirmationEmailCommand;
use App\User\Application\SignUpCommand;
use App\User\Domain\Entity\Token\ConfirmationToken;
use App\User\Infrastructure\MutationInputValidator;

class RegisterUserMutationResolver implements MutationResolverInterface
{
    public function __construct(private CommandBus $commandBus, private MutationInputValidator $validator)
    {
    }

    public function __invoke(?object $item, array $context): ?object
    {
        $this->validator->validate($item);

        $command = new SignUpCommand($item->email, $item->initials, $item->password);
        $this->commandBus->dispatch($command);

        $user = $command->getResponse()->getCreatedUser();
        $token = ConfirmationToken::generateToken($user->getId());

        $this->commandBus->dispatch(new SendConfirmationEmailCommand($user->getEmail(), $token));

        return $user;
    }
}
