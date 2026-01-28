<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\User\Application\Command\DeleteUserCommand;
use App\User\Domain\Factory\Event\UserDeletedEventFactoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Uid\Factory\UuidFactory;

final readonly class DeleteUserCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private EventBusInterface $eventBus,
        private UuidFactory $uuidFactory,
        private UserDeletedEventFactoryInterface $eventFactory
    ) {
    }

    public function __invoke(DeleteUserCommand $command): void
    {
        $user = $command->user;

        $this->userRepository->delete($user);
        $this->eventBus->publish(
            $this->eventFactory->create(
                $user,
                (string) $this->uuidFactory->create()
            )
        );
    }
}
