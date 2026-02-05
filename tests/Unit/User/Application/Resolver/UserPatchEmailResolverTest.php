<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Resolver;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Resolver\UserPatchEmailResolver;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class UserPatchEmailResolverTest extends UnitTestCase
{
    private UserPatchEmailResolver $emailResolver;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->emailResolver = new UserPatchEmailResolver();
    }

    public function testEmailResolverReturnsFallbackWhenNotProvided(): void
    {
        $result = $this->emailResolver->resolve(
            $this->faker->email(),
            'fallback@example.com',
            false
        );

        $this->assertSame('fallback@example.com', $result);
    }

    public function testEmailResolverReturnsFallbackForNullCandidate(): void
    {
        $result = $this->emailResolver->resolve(
            null,
            'fallback@example.com',
            true
        );

        $this->assertSame('fallback@example.com', $result);
    }

    public function testEmailResolverThrowsOnBlankValue(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('email must not be blank.');

        $this->emailResolver->resolve('   ', 'fallback@example.com', true);
    }

    public function testEmailResolverReturnsInvalidAddressUnchanged(): void
    {
        $result = $this->emailResolver->resolve(
            'not-an-email',
            'fallback@example.com',
            true
        );

        $this->assertSame('not-an-email', $result);
    }

    public function testEmailResolverNormalizesValidAddress(): void
    {
        $result = $this->emailResolver->resolve(
            '  User@Example.COM  ',
            'fallback@example.com',
            true
        );

        $this->assertSame('user@example.com', $result);
    }
}
