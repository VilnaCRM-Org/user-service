<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Resolver;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Resolver\UserPatchEmailResolver;
use App\User\Application\Resolver\UserPatchFieldResolver;
use App\User\Application\Resolver\UserPatchPasswordResolver;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class UserPatchResolversTest extends UnitTestCase
{
    private UserPatchEmailResolver $emailResolver;
    private UserPatchFieldResolver $fieldResolver;
    private UserPatchPasswordResolver $passwordResolver;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->emailResolver = new UserPatchEmailResolver();
        $this->fieldResolver = new UserPatchFieldResolver();
        $this->passwordResolver = new UserPatchPasswordResolver();
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

    public function testFieldResolverReturnsFallbackWhenNotProvided(): void
    {
        $result = $this->fieldResolver->resolve(
            'candidate',
            'fallback',
            false,
            'field'
        );

        $this->assertSame('fallback', $result);
    }

    public function testFieldResolverReturnsFallbackForNullCandidate(): void
    {
        $result = $this->fieldResolver->resolve(
            null,
            'fallback',
            true,
            'field'
        );

        $this->assertSame('fallback', $result);
    }

    public function testFieldResolverThrowsOnBlankValue(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('initials must not be blank.');

        $this->fieldResolver->resolve(' ', 'fallback', true, 'initials');
    }

    public function testFieldResolverReturnsTrimmedValue(): void
    {
        $result = $this->fieldResolver->resolve(
            '  Provided ',
            'fallback',
            true,
            'field'
        );

        $this->assertSame('Provided', $result);
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
