<?php

declare(strict_types=1);

namespace App\User\Domain\Collection;

use App\User\Domain\Entity\PasswordResetTokenInterface;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, PasswordResetTokenInterface>
 */
final readonly class PasswordResetTokenCollection implements
    IteratorAggregate,
    Countable
{
    /** @var list<PasswordResetTokenInterface> */
    private array $tokens;

    public function __construct(PasswordResetTokenInterface ...$tokens)
    {
        $this->tokens = array_values($tokens);
    }

    /**
     * @psalm-return ArrayIterator<int, PasswordResetTokenInterface>
     */
    #[\Override]
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->tokens);
    }

    /**
     * @psalm-return int<0, max>
     */
    #[\Override]
    public function count(): int
    {
        return count($this->tokens);
    }
}
