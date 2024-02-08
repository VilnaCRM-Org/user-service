<?php

namespace App\Tests\Unit;

use Faker\Factory;
use Faker\Generator;
use PHPUnit\Framework\TestCase;

class UnitTestCase extends TestCase
{
    protected Generator $faker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->faker = Factory::create();
    }
}
