<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Provider;

use App\Internal\HealthCheck\Domain\ValueObject\HealthCheck;
use App\Shared\Application\Provider\AllowedMethodsResourceClassProvider;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\User;

final class AllowedMethodsResourceClassProviderTest extends UnitTestCase
{
    public function testAllReturnsKnownResourceClasses(): void
    {
        $provider = new AllowedMethodsResourceClassProvider();

        $this->assertSame([User::class, HealthCheck::class], $provider->all());
    }
}
