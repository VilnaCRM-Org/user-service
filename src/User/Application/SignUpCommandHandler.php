<?php

declare(strict_types=1);

namespace App\User\Application;

use App\Shared\Domain\Bus\Command\CommandHandler;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Entity\User\User;
use App\User\Domain\UserRepositoryInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class SignUpCommandHandler implements CommandHandler
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private UserRepositoryInterface     $userRepository,
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

        $command->setResponse(new SignUpCommandResponse($user));
    }
}
