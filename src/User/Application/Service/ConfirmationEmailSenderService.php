<?php

declare(strict_types=1);

namespace App\User\Application\Service;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Factory\SendConfirmationEmailCommandFactoryInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\ConfirmationEmailFactoryInterface;
use App\User\Domain\Factory\ConfirmationTokenFactoryInterface;
use App\User\Domain\Repository\TokenRepositoryInterface;

/**
 * @psalm-api
 */
final readonly class ConfirmationEmailSenderService implements
    ConfirmationEmailSenderInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private TokenRepositoryInterface $tokenRepository,
        private ConfirmationTokenFactoryInterface $tokenFactory,
        private ConfirmationEmailFactoryInterface $confirmationEmailFactory,
        private SendConfirmationEmailCommandFactoryInterface $emailCmdFactory,
    ) {
    }

    #[\Override]
    public function send(User $user): void
    {
        $token = $this->tokenRepository->findByUserId($user->getId())
            ?? $this->tokenFactory->create($user->getId());

        $this->commandBus->dispatch(
            $this->emailCmdFactory->create(
                $this->confirmationEmailFactory->create($token, $user)
            )
        );
    }
}
