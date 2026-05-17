<?php

declare(strict_types=1);

namespace App\User\Application\Registration;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Factory\SignUpCommandFactoryInterface;
use App\User\Application\Query\FindUserByEmailQueryHandlerInterface;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Exception\DuplicateEmailException;
use RuntimeException;

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
            return $existingUser;
        }

        $command = $this->signUpCommandFactory->create(
            $email,
            $initials,
            $password
        );

        try {
            $this->commandBus->dispatch($command);
        } catch (DuplicateEmailException $error) {
            return $this->findConcurrentUserOrRethrow($email, $error);
        }

        $createdUser = $this->findUserByEmailQueryHandler->find($email);
        if ($createdUser === null) {
            throw new RuntimeException('Registered user could not be loaded.');
        }

        return $createdUser;
    }

    private function findConcurrentUserOrRethrow(
        string $email,
        DuplicateEmailException $error,
    ): UserInterface {
        $concurrentUser = $this->findUserByEmailQueryHandler->find($email);
        if ($concurrentUser !== null) {
            return $concurrentUser;
        }

        throw $error;
    }
}
