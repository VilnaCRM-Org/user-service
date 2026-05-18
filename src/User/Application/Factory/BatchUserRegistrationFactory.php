<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\Shared\Domain\Collection\DomainEventCollection;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\User\Application\DTO\BatchUserRegistrationResult;
use App\User\Domain\Collection\UserCollection;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Exception\DuplicateEmailException;
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

    /**
     * @param list<array{email: string, initials: string, password: string}> $users
     */
    public function create(array $users, UserCollection $knownUsers): BatchUserRegistrationResult
    {
        $returnedUsers = [];
        $usersToPersist = new UserCollection();
        $events = new DomainEventCollection();
        $knownUsersByEmail = $this->knownUsersByEmail($knownUsers);
        $hasher = null;

        foreach ($users as $user) {
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
     * @param array<string, UserInterface> $knownUsersByEmail
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
        $existingUser = $this->knownUser($emailKey, $knownUsersByEmail);

        if ($existingUser !== null) {
            return $existingUser;
        }

        $normalizedUser = $user;
        $normalizedUser['email'] = $emailKey;

        $createdUser = $this->createUser($normalizedUser, $hasher);
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
     * @param array<string, UserInterface> $knownUsersByEmail
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
        $knownUsersByEmail[$emailKey] = $createdUser;
    }

    /**
     * @return array<string, UserInterface>
     */
    private function knownUsersByEmail(UserCollection $knownUsers): array
    {
        $usersByEmail = [];

        foreach ($knownUsers as $knownUser) {
            $emailKey = $this->emailKey($knownUser->getEmail());

            if (isset($usersByEmail[$emailKey])) {
                throw new DuplicateEmailException($knownUser->getEmail());
            }

            $usersByEmail[$emailKey] = $knownUser;
        }

        return $usersByEmail;
    }

    /**
     * @param array<string, UserInterface> $knownUsersByEmail
     */
    private function knownUser(
        string $emailKey,
        array $knownUsersByEmail
    ): ?UserInterface {
        if (!isset($knownUsersByEmail[$emailKey])) {
            return null;
        }

        return $knownUsersByEmail[$emailKey];
    }

    private function emailKey(string $email): string
    {
        return mb_strtolower(trim($email), 'UTF-8');
    }
}
