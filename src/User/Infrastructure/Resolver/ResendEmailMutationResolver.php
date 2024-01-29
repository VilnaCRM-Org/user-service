<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Resolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Command\SendConfirmationEmailCommand;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\ConfirmationEmailFactory;
use App\User\Domain\Factory\ConfirmationTokenFactory;
use App\User\Domain\Repository\TokenRepositoryInterface;

final class ResendEmailMutationResolver implements MutationResolverInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private TokenRepositoryInterface $tokenRepository,
        private ConfirmationTokenFactory $tokenFactory,
        private ConfirmationEmailFactory $confirmationEmailFactory
    ) {
    }

    /**
     * @param User $item
     * @param array<string,string> $context
     */
    public function __invoke(?object $item, array $context): ?object
    {
        $user = $item;

        $token = $this->tokenRepository->findByUserId($user->getId())
            ?? $this->tokenFactory->create($user->getId());

        $token->send();

        $this->commandBus->dispatch(
            new SendConfirmationEmailCommand(
                $this->confirmationEmailFactory->create($token, $user)
            )
        );

        return $user;
    }
}
