<?php

namespace App\User\Application;

use App\Shared\Domain\Bus\Command\CommandHandler;
use App\User\Domain\TokenRepository;
use App\User\Domain\UserRepository;

class ConfirmUserCommandHandler implements CommandHandler
{
    public function __construct(
        private TokenRepository $tokenRepository, private UserRepository $userRepository
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
