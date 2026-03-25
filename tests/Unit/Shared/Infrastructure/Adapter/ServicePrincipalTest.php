<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Adapter;

use App\Shared\Infrastructure\Adapter\ServicePrincipal;
use App\Tests\Unit\UnitTestCase;

final class ServicePrincipalTest extends UnitTestCase
{
    public function testReturnsIdentifierAndRoles(): void
    {
        $identifier = $this->faker->uuid();
        $roles = ['ROLE_SERVICE'];
        $principal = new ServicePrincipal($identifier, $roles);

        $this->assertSame($identifier, $principal->getUserIdentifier());
        $this->assertSame($roles, $principal->getRoles());
    }

    public function testEraseCredentialsIsNoOp(): void
    {
        $principal = new ServicePrincipal($this->faker->uuid(), ['ROLE_SERVICE']);

        $principal->eraseCredentials();

        $this->assertTrue(true);
    }
}
