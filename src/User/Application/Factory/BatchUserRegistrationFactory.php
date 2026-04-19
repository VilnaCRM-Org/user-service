<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\Shared\Domain\Collection\DomainEventCollection;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\User\Application\Command\RegisterUserBatchCommand;
use App\User\Application\DTO\BatchUserRegistrationResult;
use App\User\Domain\Collection\UserCollection;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Factory\Event\UserRegisteredEventFactoryInterface;
use App\User\Domain\Factory\UserFactory;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Uid\Factory\UuidFactory;

final readonly class BatchUserRegistrationFactory
{
    public function __construct(
        private PasswordHasherFactoryInterface $hasherFactory,
        private UuidFactory $uuidFactory,
        private UserFactory $userFactory,
        private UuidTransformer $transformer,
        private UserRegisteredEventFactoryInterface $registeredEventFactory
    ) {
    }

    public function create(
        RegisterUserBatchCommand $command,
        UserCollection $knownUsers
    ): BatchUserRegistrationResult {
        $returnedUsers = [];
        $usersToPersist = new UserCollection();
        $events = new DomainEventCollection();
        $knownUsersByEmail = $this->knownUserPositions($knownUsers);
        $hasher = null;

        foreach ($command->users as $user) {
            $returnedUsers[] = $this->registerUser(
                $user,
                $knownUsers,
                $knownUsersByEmail,
                $usersToPersist,
                $events,
                $hasher
            );
        }

        return new BatchUserRegistrationResult(
            new UserCollection($returnedUsers),
            $usersToPersist,
            $events
        );
    }

    /**
     * @param array{email: string, initials: string, password: string} $user
     * @param array<string, int> $knownUsersByEmail
     */
    private function registerUser(
        array $user,
        UserCollection $knownUsers,
        array &$knownUsersByEmail,
        UserCollection $usersToPersist,
        DomainEventCollection &$events,
        ?PasswordHasherInterface &$hasher
    ): UserInterface {
        $emailKey = $this->emailKey($user['email']);
        $existingUser = $this->knownUser($emailKey, $knownUsers, $knownUsersByEmail);

        if ($existingUser !== null) {
            return $existingUser;
        }

        $createdUser = $this->createUser($user, $hasher);
        $this->rememberUser(
            $createdUser,
            $emailKey,
            $knownUsers,
            $knownUsersByEmail,
            $usersToPersist
        );
        $events = $events->add($this->registeredEventFactory->create(
            $createdUser,
            (string) $this->uuidFactory->create()
        ));

        return $createdUser;
    }

    /**
     * @param array{email: string, initials: string, password: string} $user
     */
    private function createUser(
        array $user,
        ?PasswordHasherInterface &$hasher
    ): UserInterface {
        $hasher ??= $this->hasherFactory->getPasswordHasher(User::class);

        return $this->userFactory->create(
            $user['email'],
            $user['initials'],
            $hasher->hash($user['password']),
            $this->transformer->transformFromSymfonyUuid(
                $this->uuidFactory->create()
            )
        );
    }

    /**
     * @param array<string, int> $knownUsersByEmail
     */
    private function rememberUser(
        UserInterface $createdUser,
        string $emailKey,
        UserCollection $knownUsers,
        array &$knownUsersByEmail,
        UserCollection $usersToPersist
    ): void {
        $usersToPersist->add($createdUser);
        $knownUsers->add($createdUser);
        $knownUsersByEmail[$emailKey] = $knownUsers->count() - 1;
    }

    /**
     * @return array<string, int>
     */
    private function knownUserPositions(UserCollection $knownUsers): array
    {
        $positions = [];

        foreach ($knownUsers as $position => $knownUser) {
            $positions[$this->emailKey($knownUser->getEmail())] = $position;
        }

        return $positions;
    }

    /**
     * @param array<string, int> $knownUsersByEmail
     */
    private function knownUser(
        string $emailKey,
        UserCollection $knownUsers,
        array $knownUsersByEmail
    ): ?UserInterface {
        if (!isset($knownUsersByEmail[$emailKey])) {
            return null;
        }

        return $knownUsers[$knownUsersByEmail[$emailKey]];
    }

    private function emailKey(string $email): string
    {
        return mb_strtolower($email);
    }
}
