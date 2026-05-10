<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Factory;

use App\Tests\Unit\UnitTestCase;
use App\User\Infrastructure\Factory\UlidIdFactory;
use Symfony\Component\Uid\Factory\UlidFactory;

final class UlidIdFactoryTest extends UnitTestCase
{
    public function testCreateReturnsNonEmptyString(): void
    {
        $factory = new UlidIdFactory(new UlidFactory());
        $id = $factory->create();

        $this->assertNotEmpty($id);
        $this->assertIsString($id);
    }

    public function testCreateProducesUniqueValues(): void
    {
        $factory = new UlidIdFactory(new UlidFactory());

        $this->assertNotSame($factory->create(), $factory->create());
    }
}
