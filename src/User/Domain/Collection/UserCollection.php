<?php

declare(strict_types=1);

namespace App\User\Domain\Collection;

use App\User\Domain\Entity\User;
use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;

/**
 * @implements IteratorAggregate<int, User>
 * @implements ArrayAccess<int, User>
 */
final class UserCollection implements IteratorAggregate, Countable, ArrayAccess
{
    /**
     * @param array<User> $users
     */
    public function __construct(public array $users = [])
    {
    }

    public function add(User $user): void
    {
        $this->users[] = $user;
    }

    public function remove(User $user): void
    {
        $this->users =
            array_filter($this->users, static fn ($i) => $i !== $user);
    }

    #[\Override]
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->users);
    }

    /** @psalm-suppress PossiblyUnusedMethod Called via Countable interface */
    #[\Override]
    public function count(): int
    {
        return count($this->users);
    }

    #[\Override]
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->users[$offset]);
    }

    #[\Override]
    public function offsetGet(mixed $offset): ?User
    {
        return $this->users[$offset] ?? null;
    }

    #[\Override]
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->users[] = $value;
            return;
        }

        $this->users[$offset] = $value;
    }

    #[\Override]
    public function offsetUnset(mixed $offset): void
    {
        unset($this->users[$offset]);
    }
}
