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
        $result = $this->passwordResolver->resolve(
            'candidate',
            'fallback',
            false
        );

        $this->assertSame('fallback', $result);
    }

    public function testPasswordResolverReturnsFallbackForNullCandidate(): void
    {
        $result = $this->passwordResolver->resolve(
            null,
            'fallback',
            true
        );

        $this->assertSame('fallback', $result);
    }

    public function testPasswordResolverTrimsValueWhenProvided(): void
    {
        $result = $this->passwordResolver->resolve(
            '  new-password  ',
            'fallback',
            true
        );

        $this->assertSame('new-password', $result);
    }
}
