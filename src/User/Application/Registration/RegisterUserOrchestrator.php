<?php

declare(strict_types=1);

namespace App\User\Application\Registration;

use App\Shared\Application\Bus\Guard\CommandResponseTypeGuard;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\DTO\RegisterUserCommandResponse;
use App\User\Application\Factory\SignUpCommandFactoryInterface;
use App\User\Domain\Entity\UserInterface;

final readonly class RegisterUserOrchestrator
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private SignUpCommandFactoryInterface $signUpCommandFactory,
        private CommandResponseTypeGuard $commandResponseTypeGuard,
    ) {
    }

    public function register(
        string $email,
        string $initials,
        string $password,
    ): UserInterface {
        $command = $this->signUpCommandFactory->create(
            $email,
            $initials,
            $password
        );
        $commandResponse = $this->commandResponseTypeGuard->expect(
            $this->commandBus->dispatch($command),
            RegisterUserCommandResponse::class
        );

        return $commandResponse->createdUser;
    }
}
