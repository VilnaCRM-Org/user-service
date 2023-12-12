<?php

namespace App\User\Infrastructure\User;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use App\Shared\Domain\Bus\Command\CommandBus;
use App\User\Application\SendConfirmationEmailCommand;
use App\User\Application\SignUpCommand;
use App\User\Domain\Entity\Token\ConfirmationToken;

class RegisterUserMutationResolver implements MutationResolverInterface
{
    public function __construct(private CommandBus $commandBus)
    {
    }

    public function __invoke(?object $item, array $context): ?object
    {
        $command = new SignUpCommand($item->email, $item->initials, $item->password);
        $this->commandBus->dispatch($command);

        $user = $command->getResponse()->getCreatedUser();
        $token = ConfirmationToken::generateToken($user->getId());

        $this->commandBus->dispatch(new SendConfirmationEmailCommand($user->getEmail(), $token));

        return $user;
    }
}