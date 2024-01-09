<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandler;
use App\Shared\Domain\Bus\Event\EventBus;
use App\User\Application\Command\ConfirmUserCommand;
use App\User\Domain\UserRepositoryInterface;
use App\User\Infrastructure\Exception\UserNotFoundException;

class ConfirmUserCommandHandler implements CommandHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private EventBus $eventBus
    ) {
    }

    public function __invoke(ConfirmUserCommand $command): void
    {
        $token = $command->getToken();

        $user = $this->userRepository->find($token->getUserID()) ?? throw new UserNotFoundException();
        $this->eventBus->publish($user->confirm($token));

        $this->userRepository->save($user);
    }
}
