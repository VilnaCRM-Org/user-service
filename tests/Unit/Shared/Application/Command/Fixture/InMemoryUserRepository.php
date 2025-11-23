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
        foreach ($users as $user) {
            $this->save($user);
        }
    }

    #[\Override]
    public function save(object $user): void
    {
        if (! $user instanceof UserInterface) {
            return;
        }

        $this->users[$user->getId()] = $user;
    }

    #[\Override]
    public function delete(object $user): void
    {
        if (! $user instanceof UserInterface) {
            return;
        }

        unset($this->users[$user->getId()]);
    }

    #[\Override]
    public function findByEmail(string $email): ?UserInterface
    {
        foreach ($this->users as $user) {
            if ($user->getEmail() === $email) {
                return $user;
            }
        }

        return null;
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
        foreach ($users as $user) {
            $this->save($user);
        }
    }

    /**
     * @return array<string, UserInterface>
     */
    public function all(): array
    {
        return $this->users;
    }
}
