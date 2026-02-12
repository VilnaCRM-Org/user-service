<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability\Collection;

use App\Shared\Infrastructure\Observability\ValueObject\EmfMetricDefinition;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Collection of EMF metric definitions
 *
 * @implements IteratorAggregate<int, EmfMetricDefinition>
 */
final readonly class EmfMetricDefinitionCollection implements
    IteratorAggregate,
    Countable,
    \JsonSerializable
{
    /** @var array<int, EmfMetricDefinition> */
    private array $definitions;

    public function __construct(EmfMetricDefinition ...$definitions)
    {
        $this->definitions = $definitions;
    }

    public function add(EmfMetricDefinition $definition): self
    {
        return new self(...[...$this->definitions, $definition]);
    }

    /**
     * @return ArrayIterator
     *
     * @psalm-return ArrayIterator<int, EmfMetricDefinition>
     */
    #[\Override]
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->definitions);
    }

    /**
     * @return int
     *
     * @psalm-return int<0, max>
     */
    #[\Override]
    public function count(): int
    {
        return count($this->definitions);
    }

    public function isEmpty(): bool
    {
        return $this->definitions === [];
    }

    /**
     * @return array<int, EmfMetricDefinition>
     */
    public function all(): array
    {
        return $this->definitions;
    }

    /**
     * @return array<int, array{Name: string, Unit: string}>
     */
    #[\Override]
    public function jsonSerialize(): array
    {
        return array_map(
            /**
             * @return string[]
             *
             * @psalm-return array{Name: string, Unit: string}
             */
            static fn (EmfMetricDefinition $def): array => $def->jsonSerialize(),
            $this->definitions
        );
    }
}
