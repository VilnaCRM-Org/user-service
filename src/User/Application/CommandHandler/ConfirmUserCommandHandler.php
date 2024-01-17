<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\User\Application\Command\ConfirmUserCommand;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Exception\UserNotFoundException;

class ConfirmUserCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private EventBusInterface $eventBus
    ) {
    }

    public function __invoke(ConfirmUserCommand $command): void
    {
        $token = $command->token;

        $user = $this->userRepository->find($token->getUserID()) ?? throw new UserNotFoundException();
        $this->eventBus->publish($user->confirm($token));

        $this->userRepository->save($user);
    }
}