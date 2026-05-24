<?php

declare(strict_types=1);

namespace App\User\Application\Service;

use App\Shared\Application\Bus\Guard\CommandResponseTypeGuard;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\DTO\RegisterUserCommandResponse;
use App\User\Application\Factory\SignUpCommandFactoryInterface;
use App\User\Domain\Entity\UserInterface;

final readonly class RegisterUserCommandDispatcher
{
    public function __construct(
        private SignUpCommandFactoryInterface $commandFactory,
        private CommandBusInterface $commandBus,
        private CommandResponseTypeGuard $commandResponseTypeGuard,
    ) {
    }

    public function dispatch(
        string $email,
        string $initials,
        string $password
    ): UserInterface {
        $command = $this->commandFactory->create(
            $email,
            $initials,
            $password
        );
        $response = $this->commandResponseTypeGuard->expect(
            $this->commandBus->dispatch($command),
            RegisterUserCommandResponse::class
        );

        return $response->user;
    }
}
