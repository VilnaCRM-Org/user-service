<?php

namespace App\User\Application;

use App\Shared\Domain\Bus\Command\CommandHandler;
use App\User\Domain\TokenRepository;
use App\User\Domain\UserRepository;

class ConfirmEmailCommandHandler implements CommandHandler
{
    public function __construct(
        private TokenRepository $tokenRepository, private UserRepository $userRepository
    ) {
    }

    public function __invoke(ConfirmEmailCommand $command): void
    {
        $token = $this->tokenRepository->find($command->getTokenValue());
        $this->tokenRepository->delete($token);

        $user = $this->userRepository->find($token->getUserID());
        $user->setConfirmed(true);
        $this->userRepository->save($user);
    }
}
