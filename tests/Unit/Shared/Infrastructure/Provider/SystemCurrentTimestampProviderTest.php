<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Provider;

use App\Shared\Infrastructure\Provider\SystemCurrentTimestampProvider;
use App\Tests\Unit\UnitTestCase;

final class SystemCurrentTimestampProviderTest extends UnitTestCase
{
    public function testCurrentTimestampReturnsCurrentUnixTimestamp(): void
    {
        $before = time();
        $provider = new SystemCurrentTimestampProvider();

        $timestamp = $provider->currentTimestamp();
        $after = time();

        $this->assertGreaterThanOrEqual($before, $timestamp);
        $this->assertLessThanOrEqual($after, $timestamp);
    }
}
