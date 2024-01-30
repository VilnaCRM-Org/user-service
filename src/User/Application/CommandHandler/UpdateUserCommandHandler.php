<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\User\Application\Command\UpdateUserCommand;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\Event\EmailChangedEventFactoryInterface;
use App\User\Domain\Factory\Event\PasswordChangedEventFactoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Exception\InvalidPasswordException;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Uid\Factory\UuidFactory;

final class UpdateUserCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private EventBusInterface $eventBus,
        private PasswordHasherFactoryInterface $hasherFactory,
        private UserRepositoryInterface $userRepository,
        private UuidFactory $uuidFactory,
        private EmailChangedEventFactoryInterface $emailChangedEventFactory,
        private PasswordChangedEventFactoryInterface $passwordChangedFactory,
    ) {
    }

    public function __invoke(UpdateUserCommand $command): void
    {
        $user = $command->user;
        $hasher = $this->hasherFactory->getPasswordHasher(User::class);

        if (
            !$hasher->verify(
                $user->getPassword(),
                $command->updateData->oldPassword
            )
        ) {
            throw new InvalidPasswordException();
        }

        $hashedPassword = $hasher->hash($command->updateData->newPassword);

        $events = $user->update(
            $command->updateData,
            $hashedPassword,
            (string) $this->uuidFactory->create(),
            $this->emailChangedEventFactory,
            $this->passwordChangedFactory
        );
        $this->userRepository->save($user);
        $this->eventBus->publish(...$events);
    }
}
