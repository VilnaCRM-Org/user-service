<?php

declare(strict_types=1);

namespace App\Shared\Application\Observability\Metric\Collection;

use App\Shared\Application\Observability\Metric\BusinessMetric;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Collection of business metrics to emit together
 *
 * @implements IteratorAggregate<int, BusinessMetric>
 */
final readonly class MetricCollection implements IteratorAggregate, Countable
{
    /** @var array<int, BusinessMetric> */
    private array $metrics;

    public function __construct(BusinessMetric ...$metrics)
    {
        $this->metrics = $metrics;
    }

    /**
     * @return Traversable<int, BusinessMetric>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->metrics);
    }

    public function count(): int
    {
        return count($this->metrics);
    }

    public function isEmpty(): bool
    {
        return $this->metrics === [];
    }

    /**
     * @return array<int, BusinessMetric>
     */
    public function all(): array
    {
        return $this->metrics;
    }
}
