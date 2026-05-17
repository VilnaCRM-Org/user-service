<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\User\Application\Command\ConfirmPasswordResetCommand;
use App\User\Application\Command\SignOutAllCommand;
use App\User\Application\DTO\ConfirmPasswordResetCommandResponse;
use App\User\Application\Provider\AccountLockoutProviderInterface;
use App\User\Application\Service\PasswordResetConfirmationService;
use App\User\Domain\Entity\UserInterface;
use App\User\Infrastructure\Publisher\PasswordResetConfirmationPublisherInterface;

final readonly class ConfirmPasswordResetCommandHandler implements
    CommandHandlerInterface
{
    public function __construct(
        private PasswordResetConfirmationService $confirmationService,
        private AccountLockoutProviderInterface $accountLockoutGuard,
        private CommandBusInterface $commandBus,
        private PasswordResetConfirmationPublisherInterface $publisher,
    ) {
    }

    public function __invoke(
        ConfirmPasswordResetCommand $command
    ): ConfirmPasswordResetCommandResponse {
        $user = $this->confirmationService->confirm(
            $command->token,
            $command->newPassword
        );

        $this->accountLockoutGuard->clearFailures(
            strtolower(trim($user->getEmail()))
        );
        $this->commandBus->dispatch(
            new SignOutAllCommand($user->getId(), 'password_reset')
        );
        $this->publishEvent($user);

        return new ConfirmPasswordResetCommandResponse();
    }

    private function publishEvent(UserInterface $user): void
    {
        $this->publisher->publish($user);
    }
}
