<?php

namespace App\User\Infrastructure;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use App\Shared\Domain\Bus\Command\CommandBus;
use App\User\Application\SignUpCommand;
use App\User\Domain\Entity\User;
use Symfony\Component\HttpFoundation\Response;

readonly class UserRegisterMutationResolver implements MutationResolverInterface
{
    public function __construct(private CommandBus $commandBus)
    {
    }

    public function __invoke(?object $item, array $context): ?object
    {
        try {
            $email = $context['args']['input']['email'];
            $initials = $context['args']['input']['initials'];
            $password = $context['args']['input']['password'];

            $this->commandBus->dispatch(new SignUpCommand($email, $initials, $password));

            // todo just mocking a return value for now
            return new User('a', 'b', 'c', 'd');
        } catch (\Exception $e) {
            return new User('e', "$e", 'g', 'h');
        }
    }
}
