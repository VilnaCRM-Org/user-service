<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability\Collection;

use App\Shared\Infrastructure\Observability\Exception\EmfKeyCollisionException;
use App\Shared\Infrastructure\Observability\Validator\EmfDimensionValueValidatorInterface;
use App\Shared\Infrastructure\Observability\ValueObject\EmfDimensionValue;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Collection of EMF dimension values
 *
 * Following SOLID:
 * - Single Responsibility: Collection manages its own invariants (uniqueness)
 * - Dependency Inversion: Depends on EmfDimensionValueValidatorInterface abstraction
 * - Open/Closed: Validation logic can be extended without modifying collection
 *
 * @implements IteratorAggregate<int, EmfDimensionValue>
 */
final readonly class EmfDimensionValueCollection implements IteratorAggregate, Countable
{
    /** @var array<int, EmfDimensionValue> */
    private array $dimensions;

    public function __construct(
        private EmfDimensionValueValidatorInterface $validator,
        EmfDimensionValue ...$dimensions
    ) {
        $this->assertUniqueKeys(...$dimensions);
        $this->assertAllValid(...$dimensions);

        $this->dimensions = $dimensions;
    }

    /**
     * @return Traversable<int, EmfDimensionValue>
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

    /**
     * @return array<int, EmfDimensionValue>
     */
    public function all(): array
    {
        return $this->dimensions;
    }

    /**
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

    public function keys(): EmfDimensionKeys
    {
        $keys = array_map(
            static fn (EmfDimensionValue $dim): string => $dim->key(),
            $this->dimensions
        );

        return new EmfDimensionKeys(...$keys);
    }

    private function assertUniqueKeys(EmfDimensionValue ...$dimensions): void
    {
        $keys = array_map(
            static fn (EmfDimensionValue $dimension): string => $dimension->key(),
            $dimensions
        );

        /** @var array<int, string> $duplicates */
        $duplicates = array_keys(array_filter(
            array_count_values($keys),
            static fn (int $count): bool => $count > 1
        ));

        if ($duplicates !== []) {
            throw EmfKeyCollisionException::duplicateDimensionKeys($duplicates);
        }
    }

    private function assertAllValid(EmfDimensionValue ...$dimensions): void
    {
        foreach ($dimensions as $dimension) {
            $this->validator->validate($dimension);
        }
    }
}
