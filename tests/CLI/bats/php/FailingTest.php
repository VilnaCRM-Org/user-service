<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Tests\Unit\UnitTestCase;

final class FailingTest extends UnitTestCase
{
    public function testFailure(): void
    {
        $this->assertTrue(false);
    }
}
