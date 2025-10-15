<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\EventListener\QueryParameter\Pagination;

use App\Shared\Application\EventListener\QueryParameter\Pagination\PositiveIntegerNormalizer;
use App\Tests\Unit\UnitTestCase;

final class PositiveIntegerNormalizerTest extends UnitTestCase
{
    private PositiveIntegerNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->normalizer = new PositiveIntegerNormalizer();
    }

    public function testReturnsNullForEmptyString(): void
    {
        self::assertNull($this->normalizer->normalize(''));
    }

    public function testReturnsNullForZero(): void
    {
        self::assertNull($this->normalizer->normalize('0'));
    }

    public function testNormalizesPositiveIntegerString(): void
    {
        self::assertSame(25, $this->normalizer->normalize('25'));
    }

    public function testReturnsNullForNonDigitString(): void
    {
        self::assertNull($this->normalizer->normalize('12a'));
    }
}
