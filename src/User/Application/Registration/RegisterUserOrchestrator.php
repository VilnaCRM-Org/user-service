<?php

declare(strict_types=1);

namespace App\User\Application\Registration;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Factory\SignUpCommandFactoryInterface;
use App\User\Application\Query\FindUserByEmailQueryHandlerInterface;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Exception\UserNotFoundException;

use function mb_strtolower;
use function trim;

final readonly class RegisterUserOrchestrator
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private SignUpCommandFactoryInterface $signUpCommandFactory,
        private FindUserByEmailQueryHandlerInterface $findUserByEmailQueryHandler
    ) {
    }

    public function register(
        string $email,
        string $initials,
        string $password,
    ): UserInterface {
        $normalizedEmail = $this->normalizeEmail($email);

        $existingUser = $this->findUserByEmailQueryHandler->find($normalizedEmail);
        if ($existingUser !== null) {
            return $existingUser;
        }

        $command = $this->signUpCommandFactory->create(
            $normalizedEmail,
            $initials,
            $password
        );
        $this->commandBus->dispatch($command);

        return $this->findUserByEmailQueryHandler->find($normalizedEmail)
            ?? throw new UserNotFoundException();
    }

    private function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }
}
