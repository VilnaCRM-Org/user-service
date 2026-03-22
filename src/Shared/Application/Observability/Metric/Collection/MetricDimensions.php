<?php

declare(strict_types=1);

namespace App\Shared\Application\Observability\Metric\Collection;

use App\Shared\Application\Observability\Metric\ValueObject\MetricDimension;
use ArrayIterator;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use Traversable;

/**
 * Typed collection of metric dimensions.
 *
 * @implements IteratorAggregate<int, MetricDimension>
 */
final readonly class MetricDimensions implements IteratorAggregate, Countable
{
    /** @var array<int, MetricDimension> */
    private array $dimensions;

    public function __construct(MetricDimension ...$dimensions)
    {
        $this->assertUniqueKeys(...$dimensions);

        $this->dimensions = $dimensions;
    }

    /**
     * @return Traversable<int, MetricDimension>
     */
    #[\Override]
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->dimensions);
    }

    #[\Override]
    public function count(): int
    {
        return count($this->dimensions);
    }

    public function get(string $key): ?string
    {
        foreach ($this->dimensions as $dimension) {
            if ($dimension->key() === $key) {
                return $dimension->value();
            }
        }

        return null;
    }

    public function contains(MetricDimension $expected): bool
    {
        return $this->get($expected->key()) === $expected->value();
    }

    /**
     * Infrastructure boundary helper.
     *
     * @return array<string, string>
     */
    public function toAssociativeArray(): array
    {
        $result = [];
        foreach ($this->dimensions as $dimension) {
            $result[$dimension->key()] = $dimension->value();
        }

        return $result;
    }

    private function assertUniqueKeys(MetricDimension ...$dimensions): void
    {
        $keys = $this->extractKeys($dimensions);
        $duplicates = $this->findDuplicateKeys($keys);

        if ($duplicates !== []) {
            throw new InvalidArgumentException(sprintf(
                'Duplicate metric dimension keys detected: %s',
                implode(', ', $duplicates)
            ));
        }
    }

    /**
     * @param array<int, MetricDimension> $dimensions
     *
     * @return list<string>
     */
    private function extractKeys(array $dimensions): array
    {
        return array_map(
            static fn (MetricDimension $dimension): string => $dimension->key(),
            $dimensions
        );
    }

    /**
     * @param list<string> $keys
     *
     * @return list<string>
     */
    private function findDuplicateKeys(array $keys): array
    {
        $keyCounts = array_count_values($keys);

        return array_keys(array_filter(
            $keyCounts,
            static fn (int $count): bool => $count > 1
        ));
    }
}
