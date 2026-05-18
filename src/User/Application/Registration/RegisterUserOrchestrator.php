<?php

declare(strict_types=1);

namespace App\User\Application\Registration;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Factory\SignUpCommandFactoryInterface;
use App\User\Application\Query\FindUserByEmailQueryHandlerInterface;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Exception\DuplicateEmailException;
use App\User\Domain\Exception\UserNotFoundException;

final readonly class RegisterUserOrchestrator
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private SignUpCommandFactoryInterface $signUpCommandFactory,
        private FindUserByEmailQueryHandlerInterface $findUserByEmailQueryHandler,
    ) {
    }

    public function register(
        string $email,
        string $initials,
        string $password,
    ): UserInterface {
        $existingUser = $this->findUserByEmailQueryHandler->find($email);
        if ($existingUser !== null) {
            throw new DuplicateEmailException($email);
        }

        $command = $this->signUpCommandFactory->create(
            $email,
            $initials,
            $password
        );

        $this->commandBus->dispatch($command);

        $createdUser = $this->findUserByEmailQueryHandler->find($email);
        if ($createdUser === null) {
            throw new UserNotFoundException();
        }

        return $createdUser;
    }
}
