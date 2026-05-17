<?php

declare(strict_types=1);

namespace App\User\Application\Service;

use App\Shared\Application\Bus\Guard\CommandResponseTypeGuard;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\DTO\ConfirmTwoFactorCommandResponse;
use App\User\Application\DTO\ConfirmTwoFactorDto;
use App\User\Application\Factory\ConfirmTwoFactorCommandFactoryInterface;
use App\User\Application\Resolver\CurrentUserIdentityResolver;

final readonly class ConfirmTwoFactorCommandDispatcher
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private CommandResponseTypeGuard $commandResponseTypeGuard,
        private CurrentUserIdentityResolver $userIdentityResolver,
        private ConfirmTwoFactorCommandFactoryInterface $commandFactory,
    ) {
    }

    public function dispatch(
        ConfirmTwoFactorDto $dto
    ): ConfirmTwoFactorCommandResponse {
        $command = $this->commandFactory->create(
            $this->userIdentityResolver->resolveEmail(),
            $dto->twoFactorCodeValue(),
            $this->userIdentityResolver->resolveSessionId()
        );

        return $this->commandResponseTypeGuard->expect(
            $this->commandBus->dispatch($command),
            ConfirmTwoFactorCommandResponse::class
        );
    }
}
