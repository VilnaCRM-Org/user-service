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
use InvalidArgumentException;

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
        $users = $this->usersFromCommand($command);

        if ($users === []) {
            return new RegisterUserBatchCommandResponse(new UserCollection());
        }

        $knownUsers = $this->userRepository->findByEmails(
            array_column($users, 'email')
        );
        $registrationResult = $this->batchUserRegistrationFactory->create(
            $users,
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

    /**
     * @return list<array{email: string, initials: string, password: string}>
     */
    private function usersFromCommand(RegisterUserBatchCommand $command): array
    {
        $users = [];

        foreach ($command->users as $user) {
            if (
                !isset($user['email'], $user['initials'], $user['password'])
                || !is_string($user['email'])
                || !is_string($user['initials'])
                || !is_string($user['password'])
            ) {
                throw new InvalidArgumentException(
                    'Batch user payload must contain string email, initials, and password fields.'
                );
            }

            $users[] = [
                'email' => $user['email'],
                'initials' => $user['initials'],
                'password' => $user['password'],
            ];
        }

        return $users;
    }
}
