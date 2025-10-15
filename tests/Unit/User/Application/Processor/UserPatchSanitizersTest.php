<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Processor;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Processor\UserPatchEmailSanitizer;
use App\User\Application\Processor\UserPatchNonEmptySanitizer;
use App\User\Application\Processor\UserPatchPasswordSanitizer;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class UserPatchSanitizersTest extends UnitTestCase
{
    private UserPatchEmailSanitizer $emailSanitizer;
    private UserPatchNonEmptySanitizer $nonEmptySanitizer;
    private UserPatchPasswordSanitizer $passwordSanitizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->emailSanitizer = new UserPatchEmailSanitizer();
        $this->nonEmptySanitizer = new UserPatchNonEmptySanitizer();
        $this->passwordSanitizer = new UserPatchPasswordSanitizer();
    }

    public function testEmailSanitizerReturnsFallbackWhenNotProvided(): void
    {
        $result = $this->emailSanitizer->sanitize(
            $this->faker->email(),
            'fallback@example.com',
            false
        );

        $this->assertSame('fallback@example.com', $result);
    }

    public function testEmailSanitizerReturnsFallbackForNullCandidate(): void
    {
        $result = $this->emailSanitizer->sanitize(
            null,
            'fallback@example.com',
            true
        );

        $this->assertSame('fallback@example.com', $result);
    }

    public function testEmailSanitizerThrowsOnBlankValue(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('email must not be blank.');

        $this->emailSanitizer->sanitize('   ', 'fallback@example.com', true);
    }

    public function testEmailSanitizerReturnsInvalidAddressUnchanged(): void
    {
        $result = $this->emailSanitizer->sanitize(
            'not-an-email',
            'fallback@example.com',
            true
        );

        $this->assertSame('not-an-email', $result);
    }

    public function testEmailSanitizerNormalizesValidAddress(): void
    {
        $result = $this->emailSanitizer->sanitize(
            '  User@Example.COM  ',
            'fallback@example.com',
            true
        );

        $this->assertSame('user@example.com', $result);
    }

    public function testNonEmptySanitizerReturnsFallbackWhenNotProvided(): void
    {
        $result = $this->nonEmptySanitizer->sanitize(
            'candidate',
            'fallback',
            false,
            'field'
        );

        $this->assertSame('fallback', $result);
    }

    public function testNonEmptySanitizerReturnsFallbackForNullCandidate(): void
    {
        $result = $this->nonEmptySanitizer->sanitize(
            null,
            'fallback',
            true,
            'field'
        );

        $this->assertSame('fallback', $result);
    }

    public function testNonEmptySanitizerThrowsOnBlankValue(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('initials must not be blank.');

        $this->nonEmptySanitizer->sanitize(' ', 'fallback', true, 'initials');
    }

    public function testNonEmptySanitizerReturnsTrimmedValue(): void
    {
        $result = $this->nonEmptySanitizer->sanitize(
            '  Provided ',
            'fallback',
            true,
            'field'
        );

        $this->assertSame('Provided', $result);
    }

    public function testPasswordSanitizerReturnsFallbackWhenNotProvided(): void
    {
        $result = $this->passwordSanitizer->sanitize(
            'candidate',
            'fallback',
            false
        );

        $this->assertSame('fallback', $result);
    }

    public function testPasswordSanitizerReturnsFallbackForNullCandidate(): void
    {
        $result = $this->passwordSanitizer->sanitize(
            null,
            'fallback',
            true
        );

        $this->assertSame('fallback', $result);
    }

    public function testPasswordSanitizerTrimsValueWhenProvided(): void
    {
        $result = $this->passwordSanitizer->sanitize(
            '  new-password  ',
            'fallback',
            true
        );

        $this->assertSame('new-password', $result);
    }
}
