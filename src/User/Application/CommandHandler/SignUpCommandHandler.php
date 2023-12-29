<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandler;
use App\User\Application\Command\SignUpCommand;
use App\User\Application\Command\SignUpCommandResponse;
use App\User\Domain\Entity\UserFactory;
use App\User\Domain\UserRepositoryInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class SignUpCommandHandler implements CommandHandler
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private UserRepositoryInterface $userRepository,
        private UserFactory $userFactory
    ) {
    }

    public function __invoke(SignUpCommand $command): void
    {
        $email = $command->getEmail();
        $initials = $command->getInitials();
        $password = $command->getPassword();

        $user = $this->userFactory->create($email, $initials, $password);

        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $password
        );
        $user->setPassword($hashedPassword);

        $this->userRepository->save($user);

        $command->setResponse(new SignUpCommandResponse($user));
    }
}
