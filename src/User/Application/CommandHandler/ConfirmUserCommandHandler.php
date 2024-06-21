<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\User\Application\Command\ConfirmUserCommand;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Factory\Event\UserConfirmedEventFactoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Uid\Factory\UuidFactory;

final readonly class ConfirmUserCommandHandler implements
    CommandHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private EventBusInterface $eventBus,
        private UuidFactory $uuidFactory,
        private UserConfirmedEventFactoryInterface $userConfirmedEventFactory
    ) {
    }

    public function __invoke(ConfirmUserCommand $command): void
    {
        $token = $command->token;

        $user = $this->userRepository->find(
            $token->getUserID()
        ) ?? throw new UserNotFoundException();
        $this->eventBus->publish(
            $user->confirm(
                $token,
                (string) $this->uuidFactory->create(),
                $this->userConfirmedEventFactory,
            ),
        );

        $this->userRepository->save($user);
    }
}
