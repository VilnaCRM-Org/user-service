<?php

declare(strict_types=1);

namespace App\Tests\Memory\Inventory;

use App\Tests\Memory\Support\MemoryLeakAwareWebTestCase;

final class MemoryLeakAwareWebTestCaseCoverageTest extends MemoryLeakAwareWebTestCase
{
    public function testTearDownReturnsCleanlyWhenNoKernelWasBooted(): void
    {
        self::addToAssertionCount(1);
    }
}
