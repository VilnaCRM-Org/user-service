<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\User\Application\Command\RequestPasswordResetCommand;
use App\User\Domain\Exception\UserByEmailNotFoundException;
use App\User\Domain\Exception\UserIsNotConfirmedException;
use App\User\Domain\Factory\ConfirmationEmailFactoryInterface;
use App\User\Domain\Factory\ConfirmationTokenFactoryInterface;
use App\User\Domain\Factory\Event\PasswordResetRequestedEventFactoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Uid\Factory\UuidFactory;

final readonly class RequestPasswordResetCommandHandler implements
    CommandHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private EventBusInterface $eventBus,
        private PasswordResetRequestedEventFactoryInterface $eventFactory,
        private UuidFactory $uuidFactory,
        private ConfirmationEmailFactoryInterface $confirmationEmailFactory,
        private ConfirmationTokenFactoryInterface $confirmationTokenFactory
    ) {
    }

    public function __invoke(RequestPasswordResetCommand $command): void
    {
        $user = $this->userRepository->findByEmail($command->email)
            ?? throw new UserByEmailNotFoundException($command->email);

        if (!$user->isConfirmed()) {
            throw new UserIsNotConfirmedException();
        }

        $confirmationToken = $this->confirmationTokenFactory->create($user->getId());

        $confirmationEmail = $this->confirmationEmailFactory->create(
            $confirmationToken,
            $user
        );

        $confirmationEmail->sendPasswordReset(
            (string) $this->uuidFactory->create(),
            $this->eventFactory
        );

        $this->eventBus->publish(...$confirmationEmail->pullDomainEvents());
    }
}
