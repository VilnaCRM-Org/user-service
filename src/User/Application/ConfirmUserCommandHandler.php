<?php

namespace App\User\Application;

use App\Shared\Domain\Bus\Command\CommandHandler;
use App\User\Domain\TokenRepositoryInterface;
use App\User\Domain\UserRepositoryInterface;

class ConfirmUserCommandHandler implements CommandHandler
{
    public function __construct(
        private TokenRepositoryInterface $tokenRepository, private UserRepositoryInterface $userRepository
    ) {
    }

    public function __invoke(ConfirmUserCommand $command): void
    {
        $token = $command->getToken();

        $user = $this->userRepository->find($token->getUserID());
        $user->setConfirmed(true);

        $this->tokenRepository->delete($token);
        $this->userRepository->save($user);
    }
}
