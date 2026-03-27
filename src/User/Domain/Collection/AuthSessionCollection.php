<?php

declare(strict_types=1);

namespace App\User\Domain\Collection;

use App\User\Domain\Entity\AuthSession;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, AuthSession>
 */
final readonly class AuthSessionCollection implements
    IteratorAggregate,
    Countable
{
    /** @var list<AuthSession> */
    private array $sessions;

    public function __construct(AuthSession ...$sessions)
    {
        $this->sessions = array_values($sessions);
    }

    /**
     * @psalm-return ArrayIterator<int, AuthSession>
     */
    #[\Override]
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->sessions);
    }

    /**
     * @psalm-return int<0, max>
     */
    #[\Override]
    public function count(): int
    {
        return count($this->sessions);
    }
}
