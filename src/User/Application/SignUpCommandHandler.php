<?php

declare(strict_types=1);

namespace App\User\Application;

use App\Shared\Domain\Bus\Command\CommandHandler;
use App\Shared\Domain\Bus\Event\EventBus;
use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Infrastructure\Bus\Event\UserRegisteredEvent;
use App\User\Domain\Entity\User\User;
use App\User\Domain\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class SignUpCommandHandler implements CommandHandler
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private UserRepository $userRepository,
        private EventBus $eventBus,
    ) {
    }

    public function __invoke(SignUpCommand $command): void
    {
        $id = Uuid::random()->value();
        $email = $command->getEmail();
        $initials = $command->getInitials();
        $password = $command->getPassword();

        $user = new User($id, $email, $initials, $password);

        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $password
        );
        $user->setPassword($hashedPassword);

        $this->userRepository->save($user);

        $this->eventBus->publish(new UserRegisteredEvent($user->getId(), $user->getEmail()));

        $command->setResponse(new SignUpCommandResponse($user));
    }
}
