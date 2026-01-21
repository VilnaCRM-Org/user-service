<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability\Collection;

use App\Shared\Infrastructure\Observability\Exception\EmfKeyCollisionException;
use App\Shared\Infrastructure\Observability\ValueObject\EmfMetricValue;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Collection of EMF metric values
 *
 * @implements IteratorAggregate<int, EmfMetricValue>
 */
final readonly class EmfMetricValueCollection implements IteratorAggregate, Countable
{
    /** @var array<int, EmfMetricValue> */
    private array $values;

    public function __construct(EmfMetricValue ...$values)
    {
        $this->assertUniqueNames(...$values);

        $this->values = $values;
    }

    public function add(EmfMetricValue $value): self
    {
        return new self(...[...$this->values, $value]);
    }

    /**
     * @return Traversable<int, EmfMetricValue>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->values);
    }

    public function count(): int
    {
        return count($this->values);
    }

    public function isEmpty(): bool
    {
        return $this->values === [];
    }

    /**
     * @return array<int, EmfMetricValue>
     */
    public function all(): array
    {
        return $this->values;
    }

    /**
     * @return array<string, float|int>
     */
    public function toAssociativeArray(): array
    {
        $result = [];
        foreach ($this->values as $value) {
            $result[$value->name()] = $value->value();
        }

        return $result;
    }

    private function assertUniqueNames(EmfMetricValue ...$values): void
    {
        $names = array_map(
            static fn (EmfMetricValue $value): string => $value->name(),
            $values
        );

        /** @var array<int, string> $duplicates */
        $duplicates = array_keys(array_filter(
            array_count_values($names),
            static fn (int $count): bool => $count > 1
        ));

        if ($duplicates !== []) {
            throw EmfKeyCollisionException::duplicateMetricNames($duplicates);
        }
    }
}
