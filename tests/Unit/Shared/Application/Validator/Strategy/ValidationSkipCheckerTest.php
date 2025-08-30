<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Validator\Strategy;

use App\Shared\Application\Validator\Initials;
use App\Shared\Application\Validator\Strategy\ValidationSkipChecker;
use PHPUnit\Framework\TestCase;

final class ValidationSkipCheckerTest extends TestCase
{
    private ValidationSkipChecker $skipChecker;

    protected function setUp(): void
    {
        $this->skipChecker = new ValidationSkipChecker();
    }

    public function testShouldSkipWithNullValue(): void
    {
        $constraint = new Initials();

        $result = $this->skipChecker->shouldSkip(null, $constraint);

        $this->assertTrue($result);
    }

    public function testShouldSkipWithOptionalEmptyValue(): void
    {
        $constraint = new Initials(optional: true);

        $result = $this->skipChecker->shouldSkip('', $constraint);

        $this->assertTrue($result);
    }

    public function testShouldNotSkipWithRequiredEmptyValue(): void
    {
        $constraint = new Initials(optional: false);

        $result = $this->skipChecker->shouldSkip('', $constraint);

        $this->assertFalse($result);
    }

    public function testShouldNotSkipWithValidValue(): void
    {
        $constraint = new Initials(optional: false);

        $result = $this->skipChecker->shouldSkip('valid_value', $constraint);

        $this->assertFalse($result);
    }

    public function testShouldNotSkipWithOptionalNonEmptyValue(): void
    {
        $constraint = new Initials(optional: true);

        $result = $this->skipChecker->shouldSkip('non_empty', $constraint);

        $this->assertFalse($result);
    }
}
