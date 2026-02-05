<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Resolver;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Resolver\UserPatchPasswordResolver;

final class UserPatchPasswordResolverTest extends UnitTestCase
{
    private UserPatchPasswordResolver $passwordResolver;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->passwordResolver = new UserPatchPasswordResolver();
    }

    public function testPasswordResolverReturnsFallbackWhenNotProvided(): void
    {
        $candidate = $this->faker->password();
        $fallback = $this->faker->password();

        $result = $this->passwordResolver->resolve(
            $candidate,
            $fallback,
            false
        );

        $this->assertSame($fallback, $result);
    }

    public function testPasswordResolverReturnsFallbackForNullCandidate(): void
    {
        $fallback = $this->faker->password();

        $result = $this->passwordResolver->resolve(
            null,
            $fallback,
            true
        );

        $this->assertSame($fallback, $result);
    }

    public function testPasswordResolverTrimsValueWhenProvided(): void
    {
        $password = $this->faker->password();
        $rawPassword = '  ' . $password . '  ';
        $fallback = $this->faker->password();

        $result = $this->passwordResolver->resolve(
            $rawPassword,
            $fallback,
            true
        );

        $this->assertSame($password, $result);
    }
}
