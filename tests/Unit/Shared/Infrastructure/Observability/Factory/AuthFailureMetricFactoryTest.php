<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Observability\Factory;

use App\Shared\Infrastructure\Observability\Factory\AuthFailureMetricFactory;
use App\Tests\Unit\UnitTestCase;

final class AuthFailureMetricFactoryTest extends UnitTestCase
{
    public function testCreatesSingleAuthFailureForBackend(): void
    {
        $backend = $this->faker->word();

        $metric = (new AuthFailureMetricFactory())->create($backend);

        self::assertSame('auth_failure', $metric->name());
        self::assertSame(1, $metric->value());
        self::assertSame(
            ['backend' => $backend],
            $metric->dimensions()->values()->toAssociativeArray()
        );
    }
}
