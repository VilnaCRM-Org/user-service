<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability\Collection;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Collection of dimension keys for EMF format
 *
 * Maps to the Dimensions array in CloudWatch:
 * [["Endpoint", "Operation"]]
 *
 * @implements IteratorAggregate<int, string>
 */
final readonly class EmfDimensionKeys implements IteratorAggregate, Countable, \JsonSerializable
{
    /** @var array<int, string> */
    private array $keys;

    public function __construct(string ...$keys)
    {
        $this->keys = $keys;
    }

    /**
     * @return Traversable<int, string>
     */
    #[\Override]
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->keys);
    }

    #[\Override]
    public function count(): int
    {
        return count($this->keys);
    }

    /**
     * @return array<int, string>
     */
    public function all(): array
    {
        return $this->keys;
    }

    /**
     * @return array<int, array<int, string>>
     */
    #[\Override]
    public function jsonSerialize(): array
    {
        return [$this->keys];
    }
}
