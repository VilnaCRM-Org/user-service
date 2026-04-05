<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Domain\Collection\DomainEventCollection;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\User\Application\Command\RegisterUserBatchCommand;
use App\User\Application\DTO\RegisterUserBatchCommandResponse;
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
        $userCollection = new UserCollection();
        $returnedUsers = [];
        $events = new DomainEventCollection();

        foreach ($command->users as $user) {
            [$processedUser, $events, $userCollection] = $this->processUser(
                $user,
                $events,
                $userCollection
            );
            $returnedUsers[] = $processedUser;
        }

        $this->persistUsersIfNeeded($userCollection);

        $command->setResponse(new RegisterUserBatchCommandResponse(
            new UserCollection($returnedUsers)
        ));

        $this->publishEventsIfNeeded($events);
    }

    /**
     * @param array<string> $user
     *
     * @return array{UserInterface, DomainEventCollection, UserCollection}
     */
    private function processUser(
        array $user,
        DomainEventCollection $events,
        UserCollection $usersToPersist
    ): array {
        $existingUser = $this->userRepository->findByEmail($user['email']);

        if ($existingUser !== null) {
            return [$existingUser, $events, $usersToPersist];
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
        $usersToPersist->add($createdUser);
        $events = $events->add($this->registeredEventFactory->create(
            $createdUser,
            (string) $this->uuidFactory->create()
        ));

        return [$createdUser, $events, $usersToPersist];
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
