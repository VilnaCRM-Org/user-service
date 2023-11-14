<?php

declare(strict_types=1);

namespace App\User\Application;

use App\Shared\Domain\Bus\Command\CommandHandler;
use App\Shared\Domain\UuidGenerator;
use App\User\Domain\Entity\User;
use App\User\Domain\UserRepository;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class SignUpCommandHandler implements CommandHandler
{
    public function __construct(
        private UserRepository $repository,
        private UuidGenerator $uuidGenerator,
        private ValidatorInterface $validator,
    ) {
    }

    public function __invoke(SignUpCommand $command): void
    {
        $id = $this->uuidGenerator->generate();
        $email = $command->getEmail();
        $initials = $command->getInitials();
        $password = $command->getPassword();

        $user = new User($id, $email, $initials, $password);

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            throw new \RuntimeException((string) $errors);
        } else {
            $this->repository->save($user);
        }
    }
}
