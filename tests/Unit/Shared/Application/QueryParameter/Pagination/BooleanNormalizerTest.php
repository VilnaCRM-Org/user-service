<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\QueryParameter\Pagination;

use App\Shared\Application\QueryParameter\Normalizer\BooleanNormalizer;
use App\Tests\Unit\UnitTestCase;

final class BooleanNormalizerTest extends UnitTestCase
{
    private BooleanNormalizer $normalizer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->normalizer = new BooleanNormalizer();
    }

    public function testNormalizesBooleanStrings(): void
    {
        self::assertTrue($this->normalizer->normalize('true'));
        self::assertFalse($this->normalizer->normalize('false'));
    }

    public function testNormalizesCaseInsensitiveTrimmedBooleanStrings(): void
    {
        self::assertTrue($this->normalizer->normalize(' TRUE '));
        self::assertFalse($this->normalizer->normalize(' False '));
    }

    public function testKeepsBooleanValuesUntouched(): void
    {
        self::assertTrue($this->normalizer->normalize(true));
        self::assertFalse($this->normalizer->normalize(false));
    }

    public function testReturnsNullForUnsupportedValues(): void
    {
        self::assertNull($this->normalizer->normalize(''));
        self::assertNull($this->normalizer->normalize('   '));
        self::assertNull($this->normalizer->normalize('garbage'));
        self::assertNull($this->normalizer->normalize('1'));
        self::assertNull($this->normalizer->normalize('0'));
        self::assertNull($this->normalizer->normalize('yes'));
        self::assertNull($this->normalizer->normalize('off'));
        self::assertNull($this->normalizer->normalize(1));
        self::assertNull($this->normalizer->normalize(2));
        self::assertNull($this->normalizer->normalize(new \stdClass()));
    }
}
