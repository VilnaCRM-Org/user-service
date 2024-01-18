<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\User\Application\Command\UpdateUserCommand;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Exception\InvalidPasswordException;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

class UpdateUserCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private EventBusInterface $eventBus,
        private PasswordHasherFactoryInterface $hasherFactory,
        private UserRepositoryInterface $userRepository
    ) {
    }

    public function __invoke(UpdateUserCommand $command): void
    {
        $user = $command->user;
        $hasher = $this->hasherFactory->getPasswordHasher(get_class($user));

        if (!$hasher->verify($user->getPassword(), $command->oldPassword)) {
            throw new InvalidPasswordException();
        }

        $hashedPassword = $hasher->hash($command->newPassword);

        $events = $user->update(
            $command->newEmail,
            $command->newInitials,
            $command->newPassword,
            $command->oldPassword,
            $hashedPassword
        );
        $this->userRepository->save($user);
        $this->eventBus->publish(...$events);
    }
}
