<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Resolver;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Resolver\UserPatchFieldResolver;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class UserPatchFieldResolverTest extends UnitTestCase
{
    private UserPatchFieldResolver $fieldResolver;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->fieldResolver = new UserPatchFieldResolver();
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
}
