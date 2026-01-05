<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Command\Fixture;

use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Repository\UserRepositoryInterface;

final class InMemoryUserRepository implements UserRepositoryInterface
{
    /**
     * @var array<string, UserInterface>
     */
    private array $users = [];

    public function __construct(UserInterface ...$users)
    {
        array_map(fn (UserInterface $user) => $this->save($user), $users);
    }

    #[\Override]
    public function save(object $user): void
    {
        if ($user instanceof UserInterface) {
            $this->users[$user->getId()] = $user;
        }
    }

    #[\Override]
    public function delete(object $user): void
    {
        if ($user instanceof UserInterface) {
            unset($this->users[$user->getId()]);
        }
    }

    #[\Override]
    public function findByEmail(string $email): ?UserInterface
    {
        $matcher = static fn (UserInterface $user): bool => $user->getEmail() === $email;
        return $this->findUserBy($matcher);
    }

    #[\Override]
    public function findById(string $id): ?UserInterface
    {
        return $this->users[$id] ?? null;
    }

    #[\Override]
    public function find(mixed $id, ?int $lockMode = null, ?int $lockVersion = null): ?object
    {
        return $this->findById((string) $id);
    }

    /**
     * @param array<UserInterface> $users
     */
    #[\Override]
    public function saveBatch(array $users): void
    {
        array_map(fn (UserInterface $user) => $this->save($user), $users);
    }

    /**
     * @return array<string, UserInterface>
     */
    public function all(): array
    {
        return $this->users;
    }

    private function findUserBy(callable $predicate): ?UserInterface
    {
        foreach ($this->users as $user) {
            if ($predicate($user)) {
                return $user;
            }
        }
        return null;
    }
}
