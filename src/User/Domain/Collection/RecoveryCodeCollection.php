<?php

declare(strict_types=1);

namespace App\User\Domain\Collection;

use App\User\Domain\Entity\RecoveryCode;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, RecoveryCode>
 */
final readonly class RecoveryCodeCollection implements
    IteratorAggregate,
    Countable
{
    /** @var list<RecoveryCode> */
    private array $codes;

    public function __construct(RecoveryCode ...$codes)
    {
        $this->codes = array_values($codes);
    }

    /**
     * @psalm-return ArrayIterator<int, RecoveryCode>
     */
    #[\Override]
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->codes);
    }

    /**
     * @psalm-return int<0, max>
     */
    #[\Override]
    public function count(): int
    {
        return count($this->codes);
    }
}
