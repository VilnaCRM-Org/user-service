<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\User\Application\Command\RegisterUserBatchCommand;
use App\User\Application\Command\RegisterUserBatchCommandResponse;
use App\User\Domain\Collection\UserCollection;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Factory\Event\UserRegisteredEventFactoryInterface;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Uid\Factory\UuidFactory;

final readonly class RegisterUserBatchCommandHandler implements
    CommandHandlerInterface
{
    public function __construct(
        private PasswordHasherFactoryInterface $hasherFactory,
        private UserRepositoryInterface $userRepository,
        private EventBusInterface $eventBus,
        private UuidFactory $uuidFactory,
        private UserFactory $userFactory,
        private UuidTransformer $transformer,
        private UserRegisteredEventFactoryInterface $registeredEventFactory
    ) {
    }

    public function __invoke(RegisterUserBatchCommand $command): void
    {
        $usersToPersist = [];
        $returnedUsers = [];
        $events = [];

        foreach ($command->users as $user) {
            $returnedUsers[] = $this->processUser(
                $user,
                $events,
                $usersToPersist
            );
        }

        $this->persistUsersIfNeeded($usersToPersist);

        $command->setResponse(new RegisterUserBatchCommandResponse(
            new UserCollection($returnedUsers)
        ));

        $this->publishEventsIfNeeded($events);
    }

    /**
     * @param array<string> $user
     * @param array<DomainEvent> $events
     * @param array<UserInterface> $usersToPersist
     */
    private function processUser(
        array $user,
        array &$events,
        array &$usersToPersist
    ): UserInterface {
        $existingUser = $this->userRepository->findByEmail($user['email']);

        if ($existingUser !== null) {
            return $existingUser;
        }

        $hasher = $this->hasherFactory->getPasswordHasher(User::class);
        $createdUser = $this->userFactory->create(
            $user['email'],
            $user['initials'],
            $hasher->hash($user['password']),
            $this->transformer->transformFromSymfonyUuid(
                $this->uuidFactory->create()
            )
        );
        $usersToPersist[] = $createdUser;
        $events[] = $this->registeredEventFactory->create(
            $createdUser,
            (string) $this->uuidFactory->create()
        );
        return $createdUser;
    }

    /**
     * @param array<UserInterface> $users
     */
    private function persistUsersIfNeeded(array $users): void
    {
        if ($users === []) {
            return;
        }

        $this->userRepository->saveBatch($users);
    }

    /**
     * @param array<DomainEvent> $events
     */
    private function publishEventsIfNeeded(array $events): void
    {
        if ($events === []) {
            return;
        }

        $this->eventBus->publish(...$events);
    }
}
