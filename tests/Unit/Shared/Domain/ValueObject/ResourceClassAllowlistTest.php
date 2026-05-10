<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\ValueObject;

use App\Internal\HealthCheck\Domain\ValueObject\HealthCheck;
use App\Shared\Domain\ValueObject\ResourceClassAllowlist;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\User;

final class ResourceClassAllowlistTest extends UnitTestCase
{
    public function testAllReturnsKnownResourceClasses(): void
    {
        $allowlist = new ResourceClassAllowlist();

        $this->assertSame([User::class, HealthCheck::class], $allowlist->all());
    }
}
