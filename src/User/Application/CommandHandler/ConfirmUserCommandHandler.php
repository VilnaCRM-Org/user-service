<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\User\Application\Command\ConfirmUserCommand;
use App\User\Application\Query\GetUserQueryHandler;
use App\User\Domain\Factory\Event\UserConfirmedEventFactoryInterface;
use App\User\Domain\Factory\Event\UserUpdatedEventFactoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Uid\Factory\UuidFactory;

final readonly class ConfirmUserCommandHandler implements
    CommandHandlerInterface
{
    public function __construct(
        private GetUserQueryHandler $getUserQueryHandler,
        private UserRepositoryInterface $userRepository,
        private EventBusInterface $eventBus,
        private UuidFactory $uuidFactory,
        private UserConfirmedEventFactoryInterface $userConfirmedEventFactory,
        private UserUpdatedEventFactoryInterface $userUpdatedEventFactory
    ) {
    }

    public function __invoke(ConfirmUserCommand $command): void
    {
        $token = $command->token;

        $user = $this->getUserQueryHandler->handle(
            $token->getUserID()
        );
        $eventId = (string) $this->uuidFactory->create();

        $this->eventBus->publish(
            $user->confirm(
                $token,
                $eventId,
                $this->userConfirmedEventFactory,
            ),
            $this->userUpdatedEventFactory->create($user, null, $eventId)
        );

        $this->userRepository->save($user);
    }
}
