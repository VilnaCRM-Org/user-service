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

    public function __invoke(RegisterUserBatchCommand $command): void
    {
        if ($command->users->count() === 0) {
            $command->setResponse(new RegisterUserBatchCommandResponse(
                new UserCollection()
            ));

            return;
        }

        $knownUsers = $this->userRepository->findByEmails(
            $this->emailsFromCommand($command)
        );
        $registrationResult = $this->batchUserRegistrationFactory->create(
            $command,
            $knownUsers
        );

        $this->persistUsersIfNeeded($registrationResult->usersToPersist);

        $command->setResponse(new RegisterUserBatchCommandResponse(
            $registrationResult->returnedUsers
        ));

        $this->publishEventsIfNeeded($registrationResult->events);
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

    /**
     * @return array<int, string>
     */
    private function emailsFromCommand(RegisterUserBatchCommand $command): array
    {
        $emails = [];

        foreach ($command->users->users as $user) {
            $emails[] = $user['email'];
        }

        return $emails;
    }
}
