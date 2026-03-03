<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Generator;

use App\Tests\Unit\UnitTestCase;
use App\User\Infrastructure\Generator\UlidIdGenerator;
use Symfony\Component\Uid\Factory\UlidFactory;

final class UlidIdGeneratorTest extends UnitTestCase
{
    public function testGenerateReturnsNonEmptyString(): void
    {
        $generator = new UlidIdGenerator(new UlidFactory());
        $id = $generator->generate();

        $this->assertNotEmpty($id);
        $this->assertIsString($id);
    }

    public function testGenerateProducesUniqueValues(): void
    {
        $generator = new UlidIdGenerator(new UlidFactory());

        $this->assertNotSame($generator->generate(), $generator->generate());
    }
}
