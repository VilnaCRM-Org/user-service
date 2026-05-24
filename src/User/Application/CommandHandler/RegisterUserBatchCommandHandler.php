<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Domain\Collection\DomainEventCollection;
use App\User\Application\Command\RegisterUserBatchCommand;
use App\User\Application\DTO\RegisterUserBatchCommandResponse;
use App\User\Application\Factory\BatchUserRegistrationFactory;
use App\User\Domain\Collection\UserCollection;
use App\User\Domain\Repository\UserRepositoryInterface;

final readonly class RegisterUserBatchCommandHandler implements
    CommandHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private EventBusInterface $eventBus,
        private BatchUserRegistrationFactory $batchUserRegistrationFactory
    ) {
    }

    public function __invoke(
        RegisterUserBatchCommand $command
    ): RegisterUserBatchCommandResponse {
        if ($command->users->isEmpty()) {
            return new RegisterUserBatchCommandResponse(new UserCollection());
        }

        $knownUsers = $this->userRepository->findByEmails(
            $command->users->emails()
        );
        $registrationResult = $this->batchUserRegistrationFactory->create(
            $command->users,
            $knownUsers
        );

        $this->persistUsersIfNeeded($registrationResult->usersToPersist);

        $this->publishEventsIfNeeded($registrationResult->events);

        return new RegisterUserBatchCommandResponse(
            $registrationResult->returnedUsers
        );
    }

    private function persistUsersIfNeeded(UserCollection $users): void
    {
        if ($users->count() === 0) {
            return;
        }

        $this->userRepository->saveBatch($users);
    }

    private function publishEventsIfNeeded(DomainEventCollection $events): void
    {
        if ($events->isEmpty()) {
            return;
        }

        $this->eventBus->publish(...$events);
    }
}
