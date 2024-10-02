<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\User\Application\Command\ConfirmPasswordResetCommand;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Factory\Event\PasswordChangedEventFactoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Uid\Factory\UuidFactory;

final class ConfirmPasswordResetCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordHasherFactoryInterface $passwordHasherFactory,
        private UuidFactory $uuidFactory,
        private PasswordChangedEventFactoryInterface $passwordChangedEventFactory,
        private EventBusInterface $eventBus
    ) {
    }

    public function __invoke(ConfirmPasswordResetCommand $command): void
    {
        $hasher = $this->passwordHasherFactory->getPasswordHasher(User::class);

        $user = $this->userRepository->find($command->token->getUserID())
            ?? throw new UserNotFoundException();

        $events = $user->updatePassword(
            $hasher->hash($command->newPassword),
            $this->uuidFactory,
            $this->passwordChangedEventFactory
        );

        $this->userRepository->save($user);
        $this->eventBus->publish(...$events);
    }
}
