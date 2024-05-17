<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Application\Transformer\UuidTransformer;
use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\User\Application\Command\RegisterUserBatchCommand;
use App\User\Application\Command\RegisterUserBatchCommandResponse;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Factory\Event\UserRegisteredEventFactoryInterface;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Repository\UserRepositoryInterface;
use Doctrine\Common\Collections\ArrayCollection;
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
        $users = [];
        $events = [];

        foreach ($command->users as $user) {
            $this->processUser($user, $events, $users);
        }

        $this->userRepository->saveBatch($users);

        $command->setResponse(new RegisterUserBatchCommandResponse(
            new ArrayCollection($users)
        ));

        $this->eventBus->publish(...$events);
    }

    /**
     * @param array<string> $user
     * @param array<DomainEvent> $events
     * @param array<UserInterface> $users
     */
    private function processUser(
        array $user,
        array &$events,
        array &$users
    ): void {
        $hasher = $this->hasherFactory->getPasswordHasher(User::class);
        $createdUser = $this->userFactory->create(
            $user['email'],
            $user['initials'],
            $hasher->hash($user['password']),
            $this->transformer->transformFromSymfonyUuid(
                $this->uuidFactory->create()
            )
        );
        $users[] = $createdUser;
        $events[] = $this->registeredEventFactory->create(
            $createdUser,
            (string) $this->uuidFactory->create()
        );
    }
}
