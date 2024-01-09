<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Email;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use App\Shared\Domain\Bus\Command\CommandBus;
use App\User\Application\Command\SendConfirmationEmailCommand;
use App\User\Domain\Aggregate\ConfirmationEmail;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\ConfirmationTokenFactory;
use App\User\Domain\TokenRepositoryInterface;
use App\User\Domain\UserRepositoryInterface;

class ResendEmailMutationResolver implements MutationResolverInterface
{
    public function __construct(private CommandBus $commandBus, private UserRepositoryInterface $userRepository,
        private TokenRepositoryInterface $tokenRepository, private ConfirmationTokenFactory $tokenFactory)
    {
    }

    /**
     * @param User $item
     */
    public function __invoke(?object $item, array $context): ?object
    {
        $user = $item;

        $token = $this->tokenRepository->findByUserId($user->getId()) ?? $this->tokenFactory->create($user->getId());

        $token->send();

        $this->commandBus->dispatch(
            new SendConfirmationEmailCommand(new ConfirmationEmail($token, $user)));

        return $user;
    }
}
