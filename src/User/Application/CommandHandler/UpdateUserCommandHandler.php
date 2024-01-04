<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandler;
use App\Shared\Domain\Bus\Event\EventBus;
use App\User\Application\Command\UpdateUserCommand;
use App\User\Domain\UserRepositoryInterface;
use App\User\Infrastructure\Exception\InvalidPasswordException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UpdateUserCommandHandler implements CommandHandler
{
    public function __construct(
        private EventBus $eventBus,
        private UserPasswordHasherInterface $passwordHasher,
        private UserRepositoryInterface $userRepository
    ) {
    }

    public function __invoke(UpdateUserCommand $command): void
    {
        $user = $command->user;
        if (!$this->passwordHasher->isPasswordValid($user, $command->oldPassword)) {
            throw new InvalidPasswordException();
        }

        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $command->newPassword
        );

        $events = $user->update($command->newEmail,
            $command->newInitials, $command->newPassword, $command->oldPassword, $hashedPassword);
        $this->userRepository->save($user);
        $this->eventBus->publish(...$events);
    }
}
