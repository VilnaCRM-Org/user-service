<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Observability\Collection;

use App\Shared\Infrastructure\Observability\Collection\EmfDimensionValueCollection;
use App\Shared\Infrastructure\Observability\Exception\EmfKeyCollisionException;
use App\Shared\Infrastructure\Observability\Validator\EmfDimensionValueValidator;
use App\Shared\Infrastructure\Observability\Validator\EmfDimensionValueValidatorInterface;
use App\Shared\Infrastructure\Observability\ValueObject\EmfDimensionValue;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Validator\Validation;

final class EmfDimensionValueCollectionTest extends UnitTestCase
{
    private EmfDimensionValueValidatorInterface $dimensionValidator;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->dimensionValidator = new EmfDimensionValueValidator(Validation::createValidator());
    }

    public function testCreatesCollectionWithDimensions(): void
    {
        $collection = new EmfDimensionValueCollection(
            $this->dimensionValidator,
            new EmfDimensionValue('Endpoint', 'Customer'),
            new EmfDimensionValue('Operation', 'create')
        );

        self::assertCount(2, $collection);
    }

    public function testConvertsToAssociativeArray(): void
    {
        $collection = new EmfDimensionValueCollection(
            $this->dimensionValidator,
            new EmfDimensionValue('Endpoint', 'Customer'),
            new EmfDimensionValue('Operation', 'create')
        );

        $array = $collection->toAssociativeArray();

        self::assertSame(['Endpoint' => 'Customer', 'Operation' => 'create'], $array);
    }

    public function testExtractsDimensionKeys(): void
    {
        $collection = new EmfDimensionValueCollection(
            $this->dimensionValidator,
            new EmfDimensionValue('Endpoint', 'Customer'),
            new EmfDimensionValue('Operation', 'create')
        );

        $keys = $collection->keys();

        self::assertSame(['Endpoint', 'Operation'], $keys->all());
    }

    public function testIsIterable(): void
    {
        $collection = new EmfDimensionValueCollection(
            $this->dimensionValidator,
            new EmfDimensionValue('Endpoint', 'Customer')
        );

        $items = [];
        foreach ($collection as $item) {
            $items[] = $item;
        }

        self::assertCount(1, $items);
        self::assertInstanceOf(EmfDimensionValue::class, $items[0]);
    }

    public function testAllReturnsAllDimensions(): void
    {
        $collection = new EmfDimensionValueCollection(
            $this->dimensionValidator,
            new EmfDimensionValue('Endpoint', 'Customer'),
            new EmfDimensionValue('Operation', 'create')
        );

        $all = $collection->all();

        self::assertCount(2, $all);
        self::assertInstanceOf(EmfDimensionValue::class, $all[0]);
        self::assertInstanceOf(EmfDimensionValue::class, $all[1]);
    }

    public function testThrowsExceptionOnDuplicateKeys(): void
    {
        $this->expectException(EmfKeyCollisionException::class);
        $this->expectExceptionMessage('Duplicate dimension keys detected');

        new EmfDimensionValueCollection(
            $this->dimensionValidator,
            new EmfDimensionValue('Endpoint', 'Customer'),
            new EmfDimensionValue('Endpoint', 'Order')
        );
    }
}
