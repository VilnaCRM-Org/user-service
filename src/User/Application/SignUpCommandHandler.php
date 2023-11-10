<?php

declare(strict_types=1);

namespace App\User\Application;

use App\Shared\Domain\Bus\Command\CommandHandler;
use App\Shared\Domain\UuidGenerator;
use App\User\Domain\Entity\User;
use App\User\Domain\UserRepository;

final readonly class SignUpCommandHandler implements CommandHandler
{
    public function __construct(
        private UserRepository $repository,
        private UuidGenerator $uuidGenerator
    ) {
    }

    public function __invoke(SignUpCommand $command): void
    {
        $id = $this->uuidGenerator->generate();
        $email = $command->getEmail();
        $initials = $command->getInitials();
        $password = $command->getPassword();

        $user = new User($id, $email, $initials, $password);

        $this->repository->save($user);
    }
}
