<?php

declare(strict_types=1);

namespace App\User\Application\DTO;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, BatchUserRegistrationInput>
 */
final class BatchUserRegistrationInputCollection implements
    Countable,
    IteratorAggregate
{
    /**
     * @var list<BatchUserRegistrationInput>
     */
    private array $users = [];

    public function __construct(BatchUserRegistrationInput ...$users)
    {
        foreach ($users as $user) {
            $this->add($user);
        }
    }

    public function add(BatchUserRegistrationInput $user): void
    {
        $this->users[] = $user;
    }

    public function isEmpty(): bool
    {
        return $this->users === [];
    }

    /**
     * @return list<string>
     */
    public function emails(): array
    {
        $emails = [];

        foreach ($this->users as $user) {
            $emails[] = $user->email;
        }

        return $emails;
    }

    #[\Override]
    public function count(): int
    {
        return count($this->users);
    }

    /**
     * @return Traversable<int, BatchUserRegistrationInput>
     */
    #[\Override]
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->users);
    }
}
